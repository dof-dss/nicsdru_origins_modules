<?php

declare(strict_types=1);

namespace Drupal\origins_pam\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Configure Origins pam settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_pam_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_pam.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $field_manager = \Drupal::service('entity_field.manager');
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('node');
    $tables = $storage->getTableMapping()->getAllFieldTableNames('field_additional_info');

    $node_type = \Drupal::entityTypeManager()->getDefinition('gp');
    $node_pk = $node_type->getKey('id'); // Returns 'nid'
    $node_table = $node_type->getBaseTable();

    ksm($tables, $node_pk, $node_table);


    $config = $this->config('origins_pam.alias_source_fields');
    $entity_fields = [];
    $field_storage_definitions = FieldStorageConfig::loadMultiple();

    foreach ($field_storage_definitions as $field_storage) {
      $type = $field_storage->getType();
      if (in_array($type, ['text_long', 'text_with_summary'])) {
        $entity_type = $field_storage->getTargetEntityTypeId();
        $entity_fields[$entity_type][$field_storage->getName()] = $field_storage->getLabel();
      }
    }

    foreach ($entity_fields as $entity_type => $fields) {
      $form[$entity_type] = [
        '#type' => 'details',
        '#title' => $this->t('Entity type: @entity_type', ['@entity_type' => $entity_type]),
        '#open' => TRUE,
      ];

      foreach ($fields as $field_name => $field_id) {
        $form[$entity_type][$field_name] = [
          '#type' => 'checkbox',
          '#title' => $this->t('@name (@label)', [
            '@name' => $field_name,
            '@label' => $field_id,
          ]),
          '#return_value' => $field_id,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected_fields = array_filter($form_state->cleanValues()->getValues());
    ksm($selected_fields);







//
//    $config_values = [];
//
//    if ($selected_fields) {
//      $config = $this->configFactory->getEditable('origins_pam.alias_source_fields');
//
//      foreach ($selected_fields as $field) {
//        $entity_type = substr($field, 0, strpos($field, '.'));
//        $field_name = substr($field, strpos($field, '.') + 2);
//        $field_config = FieldStorageConfig::load($field);
//        $cols = $field_config->getColumns();
//        ksm($field, $field_config->getColumns());
//        $config_values[$entity_type][$field_name] = [
//          'base_table' => $field_name . '_table',
//          'revision_table' => $field_name . '_revision_table',
//        ];
//      }
//
//      ksm($config_values);
//      $config->setData($config_values);
//      $config->save();
//    }
//


    parent::submitForm($form, $form_state);
  }

}
