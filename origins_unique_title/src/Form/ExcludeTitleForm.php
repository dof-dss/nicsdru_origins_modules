<?php

namespace Drupal\origins_unique_title\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements admin form to allow setting of audit text.
 */
class ExcludeTitleForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new AuditSettingsForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
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
  protected function getEditableConfigNames() {
    return [
      'origins_unique_title.excludesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_exclude_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('origins_unique_title.excludesettings');

    $message_exclude_ids = "If there are any specific node ID's that shouldn't be validated. List them on new lines";

    //    $message_exclude_bundles = "If there are any specific node ID's that shouldn't be validated. List them on new lines";

    $form['exclude_ids_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Node IDs'),
      '#description' => $this->t($message_exclude_ids),
      '#default_value' => $config->get('exclude_ids_list'),
    ];

    //    // Get a list of all content types.
    //    $options = [];
    //    $all_content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    //    foreach ($all_content_types as $machine_name => $content_type) {
    //      if (!in_array($machine_name, ['mas_rss', 'webform'])) {
    //        $options[$machine_name] = $content_type->label();
    //      }
    //    }
    //
    //    $form['exclude_bundles_list'] = [
    //      '#type' => 'checkboxes',
    //      '#options' => $options,
    //      '#title' => $this->t('Excluded node types'),
    //      '#description' => $this->t($message_exclude_bundles),
    //      '#default_value' => $config->get('exclude_bundles_list'),
    //    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('exclude_ids_list')) {
      $exclude_ids = explode(PHP_EOL, $form_state->getValue('exclude_ids_list'));
      foreach ($exclude_ids as $id) {
        // Make sure url is 'clean'.
        $id = str_replace(["\n", "\t", "\r"], '', $id);
        $pass = FALSE;
        if (is_numeric($id)) {
          $pass = TRUE;
        }
        if (!$pass) {
          $form_state->setErrorByName('exclude_ids_list', $this->t("Node ids must be numeric"));
        }
      }
    }
parent::validateForm($form, $form_state);

}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('origins_unique_title.excludesettings')
//      ->set('exclude_bundles_list', $form_state->getValue('exclude_bundles_list'))
      ->set('exclude_ids_list', $form_state->getValue('exclude_ids_list'))
      ->save();
  }

}
