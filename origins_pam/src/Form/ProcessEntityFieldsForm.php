<?php

declare(strict_types=1);

namespace Drupal\origins_pam\Form;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use phpDocumentor\Reflection\Types\Boolean;

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

    $batch = new BatchBuilder();
    $batch->setTitle('Updating URL aliases')
      ->setFinishCallback([self::class, 'batchFinished'])
      ->setInitMessage('Starting field alias processing')
      ->setProgressMessage('Processing...')
      ->setErrorMessage('An error occurred during processing.');

    foreach ($fields_data as $entity_type => $fields) {
      foreach ($fields as $field => $tables) {
        foreach ($tables as $table) {
          $field_name = substr($field, strrpos($field, '.') + 1);

          $db = \Drupal::database();

          $entity_ids = $db->select($table, 't')
            ->fields('t', ['entity_id', 'revision_id'])
            ->condition($field_name . '_value', 'href=["\'][^"\']*\/[^"\']*["\']', 'REGEXP')
            ->condition($field_name . '_value', 'href=["\'][^"\']*\/node[^"\']*["\']', 'NOT REGEXP')
            ->distinct()->execute()->fetchAllKeyed(0);

          $entity_id_chunks = array_chunk($entity_ids, 100, TRUE);

          foreach ($entity_id_chunks as $chunk_id => $entity_ids) {
            $args = [
              $chunk_id,
              $entity_ids,
              $table,
              $field_name,
            ];
            $batch->addOperation([self::class, 'batchProcess'], $args);
          }
        }
      }
    }

    batch_set($batch->toArray());

    $form_state->setRedirectUrl(Url::fromRoute('origins_pam.process_form'));
  }

  public static function fetchRelativeUrls($html) {
    $pattern = '/<a\b[^>]*\shref\s*=\s*["\']([^"\']*)["\'][^>]*>/i';
    preg_match_all($pattern, $html, $matches);

    $urls = [];
    foreach ($matches[1] as $href) {
      if (strpos($href, '/') === 0) {
        $urls[] = $href;
      }
    }

    return $urls;
  }

  public static function batchProcess(int $chunk_id, array $entity_ids, string $table, string $field_name, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 1000;
    }
    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['process'] = 'Form batch completed';
    }

    $context['results']['progress'] += count($entity_ids);

    $context['message'] = t('Processing batch #@batch_id batch size @batch_size for total @count items.', [
      '@batch_id' => number_format($chunk_id),
      '@batch_size' => number_format(count($entity_ids)),
      '@count' => number_format($context['sandbox']['max']),
    ]);

    $db = \Drupal::database();
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    foreach ($entity_ids as $entity_id => $revision_id) {
      $value_field = $field_name . "_value";
      $value_field_contents = $db->select($table, 't')
        ->fields('t', [$value_field])
        ->condition('t.entity_id', $entity_id)
        ->condition('t.revision_id', $revision_id)
        ->execute()->fetchField(0);

      $dom->loadHTML($value_field_contents, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_clear_errors();

      $xpath = new DOMXPath($dom);

      $query = "//a[@href and
                  @href != '' and
                  normalize-space(@href) != '' and
                  not(starts-with(@href, 'http://')) and
                  not(starts-with(@href, 'https://')) and
                  not(starts-with(@href, 'mailto:')) and
                  not(starts-with(@href, 'tel:')) and
                  not(starts-with(@href, '#')) and
                  not(starts-with(@href, '/node'))]";

      $link_elements = $xpath->query($query);

      foreach ($link_elements as $link_element) {
        if (!$link_element->hasAttribute('href')) {
          continue;
        }

        $url_alias = $link_element->getAttribute('href');

        if (self::invalidUrl($url_alias)) {
          continue;
        }

        $url_canonical = $db->select('path_alias', 'pa')
          ->fields('pa', ['path'])
          ->condition('alias', $url_alias)
          ->execute()->fetchField();

        if (empty($url_canonical)) {
          $url_canonical = $db->select('redirect', 'r')
            ->fields('r', ['redirect_redirect__uri'])
            ->condition('redirect_source__path', substr($url_alias, 1))
            ->execute()->fetchField();

          if (!empty($url_canonical)) {
            $url_canonical = str_replace(['internal:/', 'entity:'], '', $url_alias);
            $url_canonical = '/' . $url_canonical;
          }
        }

        if ($url_canonical) {
          $link_element->setAttribute('href', $url_canonical);
          $value_field_updated = $link_element->ownerDocument->saveHTML($dom);

          \Drupal::database()->update($table)
            ->fields([
              $value_field => $value_field_updated
            ])
            ->condition('entity_id', $entity_id)
            ->condition('revision_id', $revision_id)
            ->execute();
        }
      }
    }
  }

  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@process processed @count, skipped @skipped, updated @updated, failed @failed in @elapsed.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@skipped' => $results['skipped'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
        '@elapsed' => $elapsed,
      ]));
      \Drupal::logger('batch_form_example')->info(
        '@process processed @count, skipped @skipped, updated @updated, failed @failed in @elapsed.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@skipped' => $results['skipped'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
        '@elapsed' => $elapsed,
      ]);
    }
    else {
      $error_operation = reset($operations);
      if ($error_operation) {
        $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
          '%error_operation' => print_r($error_operation[0]),
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
        $messenger->addError($message);
      }
    }
  }


  public static function invalidUrl(string $url): bool {
    return (!str_starts_with($url, '/') || strpos($url, ' ') === FALSE);
  }
}
