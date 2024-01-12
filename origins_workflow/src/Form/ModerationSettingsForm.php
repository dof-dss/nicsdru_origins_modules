<?php

declare(strict_types = 1);

namespace Drupal\origins_workflow\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Origins: Moderation settings for this site.
 */
final class ModerationSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  const SETTINGS = 'origins_workflow.moderation.settings';

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_workflow_moderation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [ModerationSettingsForm::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $admin_content_links_settings = $this->config('origins_workflow.moderation.settings')->get('admin_content_links');

    $form['admin_content_links'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Admin Content section links'),
    ];

    $form['admin_content_links']['moderated_content_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Disable 'Moderated Content'."),
      '#description' => $this->t("Remove the local menu task under admin/content for the 'Moderated Content' View."),
      '#default_value' => $admin_content_links_settings['moderated_content_disable'] ?? TRUE,
      '#weight' => -10,
    ];

    $form['admin_content_links']['scheduled_content_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Disable 'Scheduled Content'."),
      '#description' => $this->t("Remove the local menu task under admin/content for the 'Scheduled Content' View."),
      '#default_value' => $admin_content_links_settings['scheduled_content_disable'] ?? TRUE,
      '#weight' => -5,
    ];

    // Workflow Moderation View displays configuration.
    $view = Views::getView('workflow_moderation');
    $displays = $view->storage->get('display');
    unset($displays['default']);

    $types = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $types[$node_type->id()] = $node_type->label();
    }

    $view_overrides = $this->config('origins_workflow.moderation.settings')->get('view_overrides');

    $form['views'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Moderation View Displays'),
    ];

    foreach ($displays as $display => $data) {
      $form[$display] = [
        '#type' => 'details',
        '#title' => $data['display_title'],
        '#group' => 'views',
      ];

      $form[$display]['node_type_filter'] = [
        '#type' => 'fieldset',
      ];

      $form[$display][$display . '_disable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable this display.'),
        '#default_value' => $view_overrides[$display]['disable'] ?? FALSE,
        '#weight' => -10,
      ];

      $form[$display]['node_type_filter'][$display . '_node_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t("Display Content types for the '@title' View.", ['@title' => $data['display_title']]),
        '#description' => $this->t('Unselect all to display all content types.'),
        '#default_value' => array_filter($view_overrides[$display]['filtered_node_types'] ?? [], 'is_string'),
        '#options' => $types,
      ];

    }

    // Hidden list of the View displays for use the submit handler.
    $form['view_displays'] = [
      '#type' => 'hidden',
      '#value' => implode(',', array_keys($displays))
    ];

    // Flush all caches as we need to clear menu, views and render.
    drupal_flush_all_caches();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $settings = [];

    // Process 'Admin Content menu links' settings.
    $settings['moderated_content_disable'] = $form_state->getValue('moderated_content_disable');
    $settings['scheduled_content_disable'] = $form_state->getValue('scheduled_content_disable');

    $this->config(ModerationSettingsForm::SETTINGS)
      ->set('admin_content_links', $settings)
      ->save();

    // Process 'Moderation View Displays' settings.
    $displays = explode(',', $form_state->getValue('view_displays'));
    $settings = [];

    foreach ($displays as $display) {
      $settings[$display]['filtered_node_types'] = $form_state->getValue($display . '_node_types');
      $settings[$display]['disable'] = $form_state->getValue($display . '_disable');
    }

    $this->config(ModerationSettingsForm::SETTINGS)
      ->set('view_overrides', $settings)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
