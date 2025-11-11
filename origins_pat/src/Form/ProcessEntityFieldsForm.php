<?php

declare(strict_types=1);

namespace Drupal\origins_pat\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Origins: Path Alias Transformer form.
 */
final class ProcessEntityFieldsForm extends FormBase {

  const REPORT_FILENAME = 'public://pat_report.csv';
  const DEADLINKS_FILENAME = 'public://pat_dead_links.csv';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_pat_process_entity_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $entity_fields = [];
    $field_storage_definitions = FieldStorageConfig::loadMultiple();

    if (\Drupal::request()->query->get('report') && file_exists(self::REPORT_FILENAME)) {
      $report_url = \Drupal::service('file_url_generator')->generateAbsoluteString(self::REPORT_FILENAME);
      $report_link = Link::fromTextAndUrl('Download report file', Url::fromUri($report_url))->toString();
      \Drupal::messenger()->addMessage($report_link);
    }

    if (\Drupal::request()->query->get('deadlinks') && file_exists(self::DEADLINKS_FILENAME)) {
      $deadlinks_url = \Drupal::service('file_url_generator')->generateAbsoluteString(self::DEADLINKS_FILENAME);
      $deadlinks_url = Link::fromTextAndUrl('Download deadlinks file', Url::fromUri($deadlinks_url))->toString();
      \Drupal::messenger()->addMessage($deadlinks_url);
    }

    $form['introduction'] = [
      '#markup' => '<p>This form will transform URLs for the selected field storage definitions, for more information visit ' . Link::fromTextAndUrl('the help page.', Url::fromRoute('help.page', ['name' => 'origins_pat']))->toString()
    ];

    // Build a list of text based field storage entries rather than field
    // instances which would mean multiple entries for the same field table.
    // e.g. Body storage exists as body, details, description etc field.
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

    // Taxonony uses a description column as part of the term record instead of
    // a 'description' table so we have to process it separately.
    $form['taxonomy_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process links in taxonomy descriptions'),
      '#return_value' => TRUE,
    ];

    $form['enable_report'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a report'),
      '#return_value' => TRUE,
    ];

    $form['report_sample_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Sample size'),
      '#description' => $this->t('A higher value will generate a larger report than a smaller value.'),
      '#options' => array_combine(range(1, 10, 1), range(1, 10, 1)),
      '#default_value' => 3,
      '#states' => [
        'visible' => [':input[name="enable_report"]' => ['checked' => TRUE]],
      ],
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
    $generate_report = FALSE;
    $report_size = 0;

    if (array_key_exists('enable_report', $selected_fields)) {
      $generate_report = $selected_fields['enable_report'];
      unset($selected_fields['enable_report']);
    }

    if (array_key_exists('report_sample_size', $selected_fields)) {
      $report_size = ($generate_report = TRUE) ? (int) $selected_fields['report_sample_size'] : 0;
      unset($selected_fields['report_sample_size']);
    }

    $batch = new BatchBuilder();
    $batch->setTitle('Updating URL aliases')
      ->setFinishCallback([self::class, 'batchFinished'])
      ->setInitMessage('Starting field alias processing')
      ->setProgressMessage('Processing...')
      ->setErrorMessage('An error occurred during processing.');

    // Process taxonomy term descriptions if selected.
    if (array_key_exists('taxonomy_descriptions', $selected_fields)) {
      unset($selected_fields['taxonomy_descriptions']);
      $this->createTaxononyDescriptionsBatch($batch, $report_size);
    }

    // Process selected field storage definitions.
    $process_fields = array_values($selected_fields);
    $this->createEntityFieldsBatch($batch, $process_fields, $report_size);

    $batch_processes = $batch->toArray();

    if ($batch_processes['operations']) {
      // Remove any existing report files.
      if (file_exists(self::REPORT_FILENAME)) {
        unlink(self::REPORT_FILENAME);
      }
      if (file_exists(self::DEADLINKS_FILENAME)) {
        unlink(self::DEADLINKS_FILENAME);
      }

      $url_parameters = [
        'deadlinks' => TRUE,
        'report' => $generate_report,
      ];

      $redirect_url = Url::fromRoute('origins_pat.process_form', $url_parameters);

      batch_set($batch->toArray());
    }
    else {
      $redirect_url = Url::fromRoute('origins_pat.process_form');
      $this->messenger()->addMessage(t('No entity fields selected for processing.'));
    }

    $form_state->setRedirectUrl($redirect_url);
  }

  /**
   * Generate batch operations for entity fields.
   *
   * @param \Drupal\Core\Batch\BatchBuilder $batch
   *   The batch object.
   * @param array $process_fields
   *   List of fields to process.
   * @param int $report_size
   *   The size of report.
   */
  public function createEntityFieldsBatch(BatchBuilder &$batch, array $process_fields, int $report_size) {
    $fields_data = [];

    // Extract the db table names (e.g. base + revision) for each storage definition.
    foreach ($process_fields as $field) {
      $entity_type = substr($field, 0, strrpos($field, '.'));
      $field_id = substr($field, strrpos($field, '.') + 1);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      // @phpstan-ignore-next-line.
      $tables = $storage->getTableMapping()->getAllFieldTableNames($field_id);
      $fields_data[$entity_type][$field] = $tables;
    }

    foreach ($fields_data as $entity_type => $fields) {
      foreach ($fields as $field => $tables) {
        foreach ($tables as $table) {

          $table_schema = [
            'table' => $table,
            'id_column' => 'entity_id',
            'value_column' => substr($field, strrpos($field, '.') + 1) . '_value',
          ];

          $entity_ids = $this->fetchEntitiesToProcess($table_schema);

          $entity_total = count($entity_ids);
          $entity_id_chunks = array_chunk($entity_ids, 250, TRUE);

          foreach ($entity_id_chunks as $chunk_id => $entity_ids) {
            $args = [
              $entity_ids,
              $entity_type,
              $table_schema,
              $chunk_id,
              $entity_total,
              $report_size,
            ];
            $batch->addOperation([self::class, 'batchProcess'], $args);
          }
        }
      }
    }
  }

  /**
   * Generate batch operations for taxonomy descriptions.
   *
   * @param \Drupal\Core\Batch\BatchBuilder $batch
   *   The batch object.
   * @param int $report_size
   *   The size of report.
   */
  public function createTaxononyDescriptionsBatch(BatchBuilder &$batch, int $report_size) {
    $table_schema = [
      'table' => 'taxonomy_term_field_data',
      'id_column' => 'tid',
      'value_column' => 'description__value',
    ];

    $terms_ids = $this->fetchEntitiesToProcess($table_schema);

    $args = [
      $terms_ids,
      'taxonomy_term',
      $table_schema,
      1,
      count($terms_ids),
      $report_size,
    ];
    $batch->addOperation([self::class, 'batchProcess'], $args);
  }

  /**
   * Returns a list of entities for the given table schema that contain relative links.
   *
   * @param array $table_schema
   *   Array of table schema data.
   */
  public function fetchEntitiesToProcess($table_schema) {
    return \Drupal::database()->select($table_schema['table'], 't')
      ->fields('t', [$table_schema['id_column'], 'revision_id'])
      ->condition($table_schema['value_column'], 'href=["\'][^"\']*\/[^"\']*["\']', 'REGEXP')
      ->distinct()->execute()->fetchAllKeyed(0);
  }

  /**
   * Batch process callback.
   */
  public static function batchProcess(array $entity_ids, string $entity_type, array $table_schema, int $chunk_id, int $entity_total, int $report_size, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $entity_total;
    }

    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['process'] = 'Finished processing ';
    }

    $context['report']['size'] = $report_size;
    $context['report']['links'] = [];
    $context['report']['dead'] = [];

    $context['results']['progress'] += count($entity_ids);

    $context['message'] = t('Processing @batch_size entities in batch #@batch_id from @table for a total of @count.', [
      '@table' => $table_schema['table'],
      '@batch_id' => number_format($chunk_id),
      '@batch_size' => number_format(count($entity_ids)),
      '@count' => number_format($context['sandbox']['max']),
    ]);

    $db = \Drupal::database();

    $services = [
      'entity_type_manager' => \Drupal::entityTypeManager(),
      'redirect_repo' => \Drupal::service('redirect.repository'),
      'path_alias_manager' => \Drupal::service('path_alias.manager'),
      'content_entity_types' => self::getContentEntityTypes(),
    ];

    // Fetch each field value, load as DOM and then process each relative link.
    foreach ($entity_ids as $entity_id => $revision_id) {
      $value_field_data['id'] = $entity_id;
      $value_field_data['type'] = $entity_type;
      $value_field_data['content'] = $db->select($table_schema['table'], 't')
        ->fields('t', [$table_schema['value_column']])
        ->condition('t.' . $table_schema['id_column'], $entity_id)
        ->condition('t.revision_id', $revision_id)
        ->execute()->fetchField(0);

      $value_field_updated = self::processFieldValue($services, $value_field_data, $context);

      if (!empty($value_field_updated)) {
        // Save directly to the field table as we don't want to use the entity
        // API which would create a revision on entity save.
        $db->update($table_schema['table'])
          ->fields([
            $table_schema['value_column'] => $value_field_updated
          ])
          ->condition($table_schema['id_column'], $entity_id)
          ->condition('revision_id', $revision_id)
          ->execute();
      }
    }

    self::writeReport($context);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@process @count entities. Updated @updated URLs, skipped @skipped and failed @failed URLs.', [
        '@process' => $results['process'],
        '@count' => number_format($results['progress']),
        '@updated' => number_format($results['updated']),
        '@skipped' => number_format($results['skipped']),
        '@failed' => number_format($results['failed']),
      ]));
      \Drupal::logger('origins_pat')->info(
        '@process @count entities. Updated @updated URLs, skipped @skipped and failed @failed URLs.', [
          '@process' => $results['process'],
          '@count' => number_format($results['progress']),
          '@updated' => number_format($results['updated']),
          '@skipped' => number_format($results['skipped']),
          '@failed' => number_format($results['failed']),
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

  /**
   * Processes HTML to update existing links with LinkIt attributes.
   *
   * @param array $services
   *   Array of container services and variables.
   * @param array $value_field_data
   *   Array of data for the field to process.
   * @param array $context
   *   Batch API context array.
   */
  public static function processFieldValue($services, $value_field_data, &$context) {
    extract($services);
    $value_field_updated = '';
    $field_is_updated = FALSE;
    $value_field_contents = $value_field_data['content'];
    $dom = new \DOMDocument();

    // Prevent DOMDocument from throwing runtime errors when it encounters
    // invalid HTML markup.
    libxml_use_internal_errors(TRUE);
    // Load the field HTML without the DTD and default root elements but wrap
    // it in a root <html> tag to prevent formatting issues during export.
    // Include the charset or we will get garbled output for punctuation.
    $dom->loadHTML('<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>' . $value_field_contents . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);

    // Match all anchor elements with a populated href attribute with a
    // relative URL.
    $anchor_query = "//a[@href and
                  @href != '' and
                  normalize-space(@href) != '' and
                  not(starts-with(@href, 'http://')) and
                  not(starts-with(@href, 'https://')) and
                  not(starts-with(@href, 'mailto:')) and
                  not(starts-with(@href, 'tel:')) and
                  not(starts-with(@href, '#'))]";

    $link_elements = $xpath->query($anchor_query);

    if (!$link_elements) {
      return "";
    }

    // @phpstan-ignore-next-line.
    $linking_entity = $entity_type_manager->getStorage($value_field_data['type'])->load($value_field_data['id']);
    // Generate a URL or identifier for the entity containing the link.
    $linking_entity_url = ($linking_entity->hasLinkTemplate('canonical')) ? $linking_entity->toUrl()->toString() : 'ID:' . $linking_entity->getType() . ':' . $linking_entity->id();

    // Add the node domain (if Domain based site), otherwise use the site name.
    if (\Drupal::service('module_handler')->moduleExists('domain')) {
      if ($linking_entity->hasField('field_domain_source')) {
        $domain = $linking_entity->get('field_domain_source')->getString();
      }
      else {
        $domain = 'unknown';
      }
    }
    else {
      $domain = $config = \Drupal::config('system.site')->get('name');
    }

    $moderation_status = 'unknown';
    if ($linking_entity->hasField('moderation_state')) {
      $moderation_status = $linking_entity->get('moderation_state')->getString();
    }

    foreach ($link_elements as $link_element) {
      $is_redirected = FALSE;
      // @phpstan-ignore-next-line.
      if (!$link_element->hasAttribute('href')) {
        continue;
      }

      // @phpstan-ignore-next-line.
      $link_url = $link_element->getAttribute('href');
      $original_link_url = $link_url;

      if (!UrlHelper::isValid($link_url) || UrlHelper::isExternal($link_url)) {
        continue;
      }

      // Redirect paths don't start with a leading slash so remove it to
      // perform a valid match.
      if (str_starts_with($link_url, '/')) {
        $link_url = substr($link_url, 1);
      }

      /* @phpstan-ignore variable.undefined */
      $redirects = $redirect_repo->findBySourcePath($link_url);
      $internal_url = '';

      if (!empty($redirects)) {
        $redirect = current($redirects);
        $internal_url = $redirect->getRedirectUrl();
        $is_redirected = TRUE;
      }
      else {
        // Add the leading slash as path aliases require it.
        if (!str_starts_with($link_url, '/')) {
          $link_url = '/' . $link_url;
        }

        /* @phpstan-ignore variable.undefined */
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
        $url_entity_id = current($route_params);

        // Check this URL is for a content entity type.
        /* @phpstan-ignore variable.undefined */
        if (!array_search($url_entity_type, $content_entity_types)) {
          continue;
        }

        // Load the entity and update the link DOM node attributes.
        /* @phpstan-ignore variable.undefined */
        $url_entity = $entity_type_manager->getStorage($url_entity_type)->load($url_entity_id);
        // @phpstan-ignore-next-line.
        $link_element->setAttribute('href', $internal_url->getInternalPath());
        // @phpstan-ignore-next-line.
        $link_element->setAttribute('data-entity-type', $url_entity->getEntityTypeId());
        // @phpstan-ignore-next-line.
        $link_element->setAttribute('data-entity-uuid', $url_entity->uuid());
        // @phpstan-ignore-next-line.
        $link_element->setAttribute('data-entity-substitution', 'canonical');
        $field_is_updated = TRUE;
        $context['results']['updated'] = $context['results']['updated'] + 1;

        if ($context['report']['size'] > 0 && (rand(1, 100) <= ($context['report']['size'] * 10))) {
          $link_entity_moderation_status = 'unknown';
          if ($url_entity->hasField('moderation_state')) {
            $link_entity_moderation_status = $url_entity->get('moderation_state')->getString();
          }

          $context['report']['links'][] = [
            $linking_entity_url,
            $moderation_status,
            '/' . $internal_url->getInternalPath(),
            $url_entity->label(),
            $link_url,
            $link_entity_moderation_status,
            ($is_redirected) ? 'Yes' : 'No',
            $domain,
          ];
        }
      }
      else {
        $context['results']['skipped'] = $context['results']['skipped'] + 1;
        $context['report']['dead'][] = [
          $linking_entity_url,
          $link_url,
          $domain,
          $moderation_status
        ];
      }
    }

    if ($field_is_updated) {
      // Strip the root element added to preserve formatting on export.
      $value_field_updated = str_replace([
        '<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>',
        '</html>'
      ], '', $dom->saveHTML());
    }

    return $value_field_updated;
  }

  /**
   * Writes link update data to a report file.
   *
   * @param array $context
   *   Array of context data for a batch or taxonomy process.
   */
  public static function writeReport($context) {
    if (!empty($context['report']['links'])) {
      $report_file = fopen(self::REPORT_FILENAME, 'a');

      foreach ($context['report']['links'] as $report_entry) {
        fputcsv($report_file, $report_entry, ',', '"', '');
      }

      fclose($report_file);
    }
    if (!empty($context['report']['dead'])) {
      $report_file = fopen(self::DEADLINKS_FILENAME, 'a');

      foreach ($context['report']['dead'] as $report_entry) {
        fputcsv($report_file, $report_entry, ',', '"', '');
      }

      fclose($report_file);
    }
  }

  /**
   * Generates a list of content entity types.
   *
   * @return array
   *   List of machine names.
   */
  public static function getContentEntityTypes() {
    $content_entity_types = [];

    // Build a list of content type entities that we can use later to match
    // against a link's URL parameters and reject any invalid, non-content
    // entity, URLs.
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        if ($entity_type->getLinkTemplate('canonical')) {
          $content_entity_types[] = $entity_type->id();
        }
      }
    }

    return $content_entity_types;
  }

}
