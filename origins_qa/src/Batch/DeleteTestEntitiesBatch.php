<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Batch;

/**
 * Batch callbacks for deleting automated test entities.
 */
final class DeleteTestEntitiesBatch {

  /**
   * Deletes a chunk of entities.
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
    $entities = $storage->loadMultiple($ids);

    if ($entities !== []) {
      $storage->delete($entities);
    }

    $context['results']['entity_type_id'] = $entity_type_id;
    $context['results']['deleted'] = ($context['results']['deleted'] ?? 0) + \count($entities);
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
