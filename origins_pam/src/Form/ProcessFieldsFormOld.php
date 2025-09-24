<?php

declare(strict_types=1);

namespace Drupal\origins_pam\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Process path aliases in selected entity fields.
 */
final class ProcessFieldsFormOld extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_pam_process_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {



    $form['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Process links'),
    ];

    return $form;
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
  }

}
