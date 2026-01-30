<?php

namespace Drupal\origins_workflow\Plugin\RevisionManager;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\revision_manager\Plugin\RevisionManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes revisions based on Origins workflow rules.
 *
 * @RevisionManager(
 *   id = "origins_default",
 *   label = @Translation("Origins"),
 *   description = @Translation(
 *     "Default revision cleanup for DoF sites"
 *   )
 * )
 */
final class OriginsDefault extends RevisionManagerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum age (in months) of revisions to keep'),
      '#description' => $this->t("Only 'draft' and 'needs review' revisions x months older than the published revision will be removed."),
      '#min' => 1,
      '#max' => 48,
      '#required' => TRUE,
      '#default_value' => $this->configuration['age'] ?? 6,
    ];

    $form['all_revisions_nodes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of nodes for which all old revisions should be deleted'),
      '#description' => $this->t('Space separated list of nodes to delete all revision states including published and archived.'),
      '#default_value' => $this->configuration['all_revisions_nodes'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevisions(RevisionableInterface $entity): array {
    $published_vid = $this->entityTypeManager->getStorage('node')->getLatestRevisionId($entity->id());
    // Replace any commas with spaces and generate array of nids from config setting.
    $all_revisions_nodes = explode(' ', str_replace(',', ' ', trim($this->configuration['all_revisions_nodes']))) ?? [];
    $age_comparison = sprintf('-%d months', (int) ($this->configuration['age'] ?? 6));
    // @phpstan-ignore-next-line
    $age_comparison_ts = strtotime($age_comparison, (int) $entity->getRevisionCreationTime());

    // Fetch moderation status of latest revision.
    $query = $this->database->select('content_moderation_state_field_data', 'msfd');
    $query->fields('msfd', ['moderation_state']);
    $node_status = $query->condition('msfd.content_entity_id', $entity->id())
      ->condition('msfd.content_entity_revision_id', $published_vid)
      ->execute()
      ->fetchField();

    if ($node_status == 'draft' || $node_status == 'needs_review') {
      return [];
    }

    // Fetch all revisions older than the published revision.
    $query = $this->database->select('node_revision', 'nr');
    $query->leftJoin(
      'content_moderation_state_field_revision',
      'msfr',
      'nr.nid = msfr.content_entity_id AND nr.vid = msfr.content_entity_revision_id'
    );
    $query->addField('nr', 'vid');
    $query->condition('nr.nid', $entity->id());
    $query->condition('nr.vid', $published_vid, '<');
    $query->condition('nr.revision_timestamp', $age_comparison_ts, '<');

    // Ignore moderation state when current nid is present in all_revisions_nodes list.
    if (!in_array($entity->id(), $all_revisions_nodes)) {
      $query->condition('msfr.moderation_state', ['published', 'archived'], 'NOT IN');
    }

    $vids = $query->execute()->fetchCol();

    return $vids;
  }

}
