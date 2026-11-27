<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Batch;

/**
 * Batch callbacks for deleting automated test entities.
 */
final class DeleteTestEntitiesBatch {

  /**
   * Maximum number of failed entity labels shown in the completion message.
   */
  protected const FAILURE_MESSAGE_LIMIT = 10;

  /**
   * Deletes a chunk of entities.
   *
   * Entities are deleted individually so that one entity that cannot be
   * deleted, e.g. a topic which still has active child content, is logged and
   * skipped rather than halting the batch.
   *
   * @param string $entity_type_id
   *   The entity type ID, e.g. 'node' or 'media'.
   * @param int[] $ids
   *   The entity IDs to delete.
   * @param array<string, mixed>|\ArrayAccess<string, mixed> $context
   *   The batch context.
   */
  public static function process(string $entity_type_id, array $ids, array|\ArrayAccess &$context): void {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $logger = \Drupal::logger('origins_qa');
    $deleted = 0;

    foreach ($storage->loadMultiple($ids) as $entity) {
      try {
        $storage->delete([$entity]);
        $deleted++;
      }
      catch (\Exception $e) {
        $label = $entity->label() . ' (' . $entity->bundle() . ', ' . $entity->id() . ')';
        $context['results']['failed'][] = $label;
        $logger->warning('Unable to delete automated test content @label: @message', [
          '@label' => $label,
          '@message' => \strip_tags($e->getMessage()),
        ]);
      }
    }

    $context['results']['entity_type_id'] = $entity_type_id;
    $context['results']['deleted'] = ($context['results']['deleted'] ?? 0) + $deleted;
    $context['message'] = \t('Deleted @count…', [
      '@count' => self::countLabel($entity_type_id, (int) $context['results']['deleted']),
    ]);
  }

  /**
   * Reports the outcome of the batch.
   *
   * @param bool $success
   *   Whether the batch completed without a fatal error.
   * @param array<string, mixed> $results
   *   The results gathered by the batch operations.
   * @param array<int, mixed> $operations
   *   Any operations that did not complete.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $deleted = (int) ($results['deleted'] ?? 0);
    $entity_type_id = (string) ($results['entity_type_id'] ?? 'node');
    $count = self::countLabel($entity_type_id, $deleted);
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addStatus(\t('Deleted @count of automated test content.', ['@count' => $count]));
    }

    $failed = (array) ($results['failed'] ?? []);
    if ($failed !== []) {
      $items = \array_slice($failed, 0, self::FAILURE_MESSAGE_LIMIT);
      $remaining = \count($failed) - \count($items);
      if ($remaining > 0) {
        $items[] = \t('…and @count more', ['@count' => $remaining]);
      }
      $build = [
        '#theme' => 'item_list',
        '#title' => \t('@count could not be deleted. See the recent log messages for details.', [
          '@count' => self::countLabel($entity_type_id, \count($failed)),
        ]),
        '#items' => $items,
      ];
      $messenger->addWarning(\Drupal::service('renderer')->renderInIsolation($build));
    }
    else {
      $messenger->addError(\t('An error occurred after deleting @count of automated test content. Check the logs and try again.', ['@count' => $count]));
    }

    \Drupal::logger('origins_qa')->notice('Deleted @count of automated test content.', ['@count' => $count]);
  }

  /**
   * Formats a count using the entity type's count label.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $count
   *   The number of entities.
   *
   * @return string
   *   For example "3 content items" or "1 media item".
   */
  protected static function countLabel(string $entity_type_id, int $count): string {
    return (string) \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getCountLabel($count);
  }

}
