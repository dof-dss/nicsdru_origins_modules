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
 * @see https://digitaldevelopment.atlassian.net/wiki/x/B4BExQ
 *
 * @RevisionManager(
 *   id = "origins_default",
 *   label = @Translation("Origins"),
 *   description = @Translation(
 *     "Default revision cleanup for DoF sites"
 *   )
 * )
 *
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

    $form['introduction'] = [
      '#markup' => 'Process revisions in accordance with the <a href="https://digitaldevelopment.atlassian.net/wiki/x/B4BExQ"> DoF Revisions requirements</a>.'
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum age (in months) of revisions to keep'),
      '#description' => $this->t("Only 'Draft' and 'Needs review' revisions x months older than the current published revision will be removed, 'Published' and 'Archived' revisions will be preserved."),
      '#min' => 1,
      '#max' => 48,
      '#required' => TRUE,
      '#default_value' => $this->configuration['age'] ?? 6,
    ];

    $form['all_revisions_nodes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of nodes for which all old revisions should be deleted after the minimum age'),
      '#description' => $this->t("Space separated list of nodes to delete all revision states including 'Published' and 'Archived'."),
      '#default_value' => $this->configuration['all_revisions_nodes'] ?? '',
    ];

    $form['all_revisions_nodes_no_age'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of nodes for which all old revisions should be deleted'),
      '#description' => $this->t("Space separated list of nodes to delete all revision states including 'Published' and 'Archived'."),
      '#default_value' => $this->configuration['all_revisions_nodes_no_age'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevisions(RevisionableInterface $entity): array {
    $published_vid = $this->entityTypeManager->getStorage('node')->getLatestRevisionId($entity->id());
    // Replace any commas with spaces to create list of nids to remove all expired revision types.
    $all_revisions_nodes = explode(' ', str_replace(',', ' ', trim($this->configuration['all_revisions_nodes']))) ?? [];
    $all_revisions_nodes_no_age = explode(' ', str_replace(',', ' ', trim($this->configuration['all_revisions_nodes_no_age']))) ?? [];
    $age_comparison = sprintf('-%d months', (int) ($this->configuration['age'] ?? 6));
    // @phpstan-ignore-next-line
    $age_comparison_timestamp = strtotime($age_comparison, (int) $entity->getRevisionCreationTime());

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

    // Ignore age comparison when current nid is present in all_revisions_nodes_no_age list.
    if (!in_array($entity->id(), $all_revisions_nodes_no_age)) {
      $query->condition('nr.revision_timestamp', $age_comparison_timestamp, '<');
    }

    // Ignore moderation state when current nid is present in all_revisions_nodes lists.
    if (!in_array($entity->id(), $all_revisions_nodes) && !in_array($entity->id(), $all_revisions_nodes_no_age)) {
      $query->condition('msfr.moderation_state', ['published', 'archived'], 'NOT IN');
    }

    $vids = $query->execute()->fetchCol();

    return $vids;
  }

}
