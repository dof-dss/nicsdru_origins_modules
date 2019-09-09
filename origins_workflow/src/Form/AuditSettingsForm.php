<?php

namespace Drupal\origins_workflow\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements admin form to allow setting of audit text.
 */
class AuditSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates a new AuditSettingsForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('nics_audit_settings_form'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'origins_workflow.auditsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('origins_workflow.auditsettings');

    $form['audit_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audit button text'),
      '#description' => $this->t('Text to be displayed on the button that the editor presses to audit the content.'),
      '#default_value' => $config->get('audit_button_text'),
    ];

    $form['audit_button_hover_text'] = [
      '#type' => 'textfield',
      '#size' => 130,
      '#title' => $this->t('Audit button hover text'),
      '#description' => $this->t('Text to be displayed when the editor hovers their mouse over the audit button.'),
      '#default_value' => $config->get('audit_button_hover_text'),
    ];

    $form['audit_confirmation_text'] = [
      '#type' => 'textfield',
      '#size' => 130,
      '#title' => $this->t('Audit confirmation text'),
      '#description' => $this->t('Ask the editor to confirm that they have audited the content.'),
      '#default_value' => $config->get('audit_confirmation_text'),
    ];

    // Get a list of all content types.
    $options = [];
    $all_content_types = NodeType::loadMultiple();
    foreach ($all_content_types as $machine_name => $content_type) {
      if (!in_array($machine_name, ['mas_rss', 'webform'])) {
        $options[$machine_name] = $content_type->label();
      }
    }

    $form['audit_content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Content types to be audited'),
      '#default_value' => $config->get('audit_content_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Check to see if any new content types have been selected.
    $config = $this->config('origins_workflow.auditsettings');
    $old_content_type_list = $config->get('audit_content_types');
    $new_content_type_list = $form_state->getValue('audit_content_types');
    // Find content types that have just been added.
    foreach ($new_content_type_list as $this_type) {
      if (!$this_type) {
        continue;
      }
      if (!$old_content_type_list[$this_type]) {
        $this->addAuditField($this_type);
      }
    }
    // Find content types that have just been removed.
    foreach ($old_content_type_list as $this_type) {
      if (!$this_type) {
        continue;
      }
      if (!$new_content_type_list[$this_type]) {
        if (!$this->removeAuditField($this_type)) {
          return;
        }
      }
    }

    $this->config('origins_workflow.auditsettings')
      ->set('audit_button_text', $form_state->getValue('audit_button_text'))
      ->set('audit_button_hover_text', $form_state->getValue('audit_button_hover_text'))
      ->set('audit_confirmation_text', $form_state->getValue('audit_confirmation_text'))
      ->set('audit_content_types', $form_state->getValue('audit_content_types'))
      ->save();
  }

  /**
   * Remove audit field from the content type.
   */
  public function removeAuditField($type) {
    // Remove audit field from this content type.
    $field = FieldConfig::loadByName('node', $type, 'field_next_audit_due');
    if (!empty($field)) {
      // See if there is any data in this field.
      $ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', $type)
        ->exists('field_next_audit_due')
        ->execute();

      if (count($ids) > 0) {
        // Some present, abort.
        $this->messenger->deleteAll();
        $this->messenger->addError(t('Audit data exists for @type - auditing cannot be disabled', ['@type' => $type]));
        return FALSE;
      }

      $field->delete();
    }

    // Log it.
    $this->logger->notice(t("Content auditing disabled for @type", ['@type' => $type]));

    $this->messenger->addMessage(t('Auditing successfully disabled for @type', ['@type' => $type]));

    return TRUE;
  }

  /**
   * Add audit field to the content type.
   */
  private function addAuditField($type) {
    // Add an audit field to the content type.
    //$field_storage = FieldStorageConfig::loadByName('node', 'field_next_audit_due');
    $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->load("node.field_next_audit_due");
    $field = FieldConfig::loadByName('node', $type, 'field_next_audit_due');
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type,
        'label' => 'Next audit due',
        'settings' => ['display_summary' => TRUE],
        'description' => t('The date when this item is due for audit'),
      ]);
      $field->save();

      // Assign widget settings for the default form mode.
      $entity_form_display = EntityFormDisplay::load('node.' . $type . '.default');
      if (isset($entity_form_display)) {
        $entity_form_display->setComponent('field_next_audit_due', [
          'type' => 'datetime_default',
          'weight' => 100,
        ])->save();
      }

      // Log it.
      $this->logger->notice(t("Content auditing enabled for @type", ['@type' => $type]));

      $this->messenger->addMessage(t('Auditing successfully enabled for @type', ['@type' => $type]));
    }
  }

}
