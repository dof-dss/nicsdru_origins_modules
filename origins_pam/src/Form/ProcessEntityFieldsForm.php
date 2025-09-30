<?php

declare(strict_types=1);

namespace Drupal\origins_pam\Form;

use DOMDocument;
use DOMXPath;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
            ->distinct()->execute()->fetchAllKeyed(0);

          $entity_id_chunks = array_chunk($entity_ids, 500, TRUE);

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
      $context['results']['process'] = 'Finished processing ';
    }

    $context['results']['progress'] += count($entity_ids);

    $context['message'] = t('Processing @table batch: #@batch_id.Batch size @batch_size for total @count items.', [
      '@table' => $table,
      '@batch_id' => number_format($chunk_id),
      '@batch_size' => number_format(count($entity_ids)),
      '@count' => number_format($context['sandbox']['max']),
    ]);

    $db = \Drupal::database();
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $redirect_repo = \Drupal::service('redirect.repository');
    $path_alias_manager = \Drupal::service('path_alias.manager');


    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $content_entity_types = [];

    foreach ($entity_types as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        if ($entity_type->getLinkTemplate('canonical')) {
          $content_entity_types[] = $entity_type->id();
        }
      }
    }


    foreach ($entity_ids as $entity_id => $revision_id) {
      $field_is_updated = FALSE;
      $value_field = $field_name . "_value";
      $value_field_contents = $db->select($table, 't')
        ->fields('t', [$value_field])
        ->condition('t.entity_id', $entity_id)
        ->condition('t.revision_id', $revision_id)
        ->execute()->fetchField(0);

      $dom->loadHTML('<html>' . $value_field_contents . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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

        $link_url = $link_element->getAttribute('href');

        if (!UrlHelper::isValid($link_url) || UrlHelper::isExternal($link_url)) {
          continue;
        }

        if (str_starts_with($link_url, '/')) {
          $link_url = substr($link_url, 1);
        }

        $redirects = $redirect_repo->findBySourcePath($link_url);

        if (!empty($redirects)) {
          $redirect = current($redirects);
          $internal_url = $redirect->getRedirectUrl();
        } else {

          if (!str_starts_with($link_url, '/')) {
            $link_url = '/' . $link_url;
          }

          $path_alias_url = $path_alias_manager->getPathByAlias($link_url);

          if (!empty($path_alias_url)) {
            $internal_url = Url::fromUserInput($path_alias_url);
          }
        }

        if ($internal_url->isRouted()) {
          $route_params = $internal_url->getRouteParameters();

          if (empty($route_params)) {
            continue;
          }

          $url_entity_type = key($route_params);

          if (!array_search($url_entity_type, $content_entity_types)) {
            continue;
          }

          $url_entity_id = current($route_params);
          $url_entity = \Drupal::entityTypeManager()->getStorage($url_entity_type)->load($url_entity_id);
          $link_element->setAttribute('href', $internal_url->getInternalPath());
          $link_element->setAttribute('data-entity-type', $url_entity->getEntityTypeId());
          $link_element->setAttribute('data-entity-uuid', $url_entity->uuid());
          $link_element->setAttribute('data-entity-substitution', 'canonical');;
          $field_is_updated = TRUE;
        } else {
          $context['results']['skipped'] = $context['results']['updated'] + 1;
        }

      }

      if ($field_is_updated) {
        $value_field_updated = str_replace(['<html>','</html>'] , '' , $dom->saveHTML());;

        $result = \Drupal::database()->update($table)
          ->fields([
            $value_field => $value_field_updated
          ])
          ->condition('entity_id', $entity_id)
          ->condition('revision_id', $revision_id)
          ->execute();

        $context['results']['updated'] = $context['results']['updated'] + 1;
      }
    }
  }

  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@process @count nodes. Skipped @skipped URLs, updated @updated URLs, failed @failed URLs.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@skipped' => $results['skipped'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
      ]));
      \Drupal::logger('batch_form_example')->info(
        '@process @count nodes. Skipped @skipped URLs, updated @updated URLs, failed @failed URLs.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@skipped' => $results['skipped'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
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

}
