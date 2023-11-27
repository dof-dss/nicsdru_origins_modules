<?php

declare(strict_types = 1);

namespace Drupal\origins_add_content\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Origins Add content settings.
 */
final class AddContentPageSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_add_content_add_content_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_add_content.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('origins_add_content.settings');
    $entities = $this->entityTypeManager->getDefinitions();
    $options = [];
    $unavailable = [];

    foreach ($entities as $entity_type) {

      if ($entity_type instanceof ContentEntityTypeInterface) {
        $links = $entity_type->get('links');
        if (!array_key_exists('add-form',$links)) {
          $unavailable[] = $entity_type->getLabel();
        }
        else {
          $options[$entity_type->id()] = $entity_type->getLabel();
        }
      }
    }

    $form['entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entities to create links for'),
      '#options' => $options,
      '#default_value' => $config->get('entities') ?: [],
      '#description' => $this->t('Selected entities will have a link added to the @add_content_link (node/add) admin page.', ['@add_content_link' => Link::createFromRoute('Add content', 'node.add_page')->toString()])
    ];

    $form['unavailable'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t("Unavailable because they do not define the 'add-form' entity attribute"),
      '#items' => $unavailable,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $entity_types = array_keys(array_filter($values['entities']));

    $this->config('origins_add_content.settings')
      ->set('entities', $entity_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
