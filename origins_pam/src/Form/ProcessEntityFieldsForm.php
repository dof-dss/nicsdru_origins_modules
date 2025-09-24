<?php

declare(strict_types=1);

namespace Drupal\origins_pam\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a Origins: Path Alias Manager form.
 */
final class ProcessEntityFieldsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_pam_process_entity_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
        '#title' => $this->t('@entity_type', ['@entity_type' => $entity_type]),
        '#open' => TRUE,
      ];

      foreach ($fields as $field_name => $field_id) {
        $form[$entity_type]["$entity_type--$field_name"] = [
          '#type' => 'checkbox',
          '#title' => $this->t('@name (@label)', [
            '@name' => $field_name,
            '@label' => $field_id,
          ]),
          '#return_value' => $field_id,
        ];
      }
    }

    $form['taxonomy_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process links in taxonomy descriptions'),
      '#return_value' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Process'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected_fields = array_filter($form_state->cleanValues()->getValues());
    $process_taxonomy_descriptions = FALSE;

    if (array_key_exists('taxonomy_descriptions', $selected_fields)) {
      $process_taxonomy_descriptions = TRUE;
      unset($selected_fields['taxonomy_descriptions']);
    }
    $process_fields = array_values($selected_fields);

    $entity_type_manager = \Drupal::entityTypeManager();
    $fields_data = [];

    foreach ($process_fields as $field) {
      $entity_type = substr($field, 0, strrpos($field, '.'));
      $field_id = substr($field, strrpos($field, '.') + 1);
      $storage = $entity_type_manager->getStorage($entity_type);
      $tables = $storage->getTableMapping()->getAllFieldTableNames($field_id);
      $fields_data[$entity_type][$field] = $tables;
    }

    ksm($fields_data);

  }

}
