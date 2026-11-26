<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Form;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Confirms and deletes nodes created by automated tests.
 *
 * Nodes are deleted bundle by bundle in the order configured on the QA
 * settings form, newest first within each bundle. Bundles missing from that
 * order are deleted last, and bundles marked as ignored are never deleted.
 */
final class DeleteTestNodesForm extends DeleteTestEntitiesFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityTypeId(): string {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLabelPrefix(): string {
    return self::NODE_TITLE_PREFIX;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterTestEntityQuery(QueryInterface $query): void {
    $ignored = \array_values(\array_filter((array) $this->config('origins_qa.settings')->get('node_delete_ignored_bundles')));
    if ($ignored !== []) {
      $query->condition('type', $ignored, 'NOT IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findTestEntities(): array {
    $ids = parent::findTestEntities();
    $order = \array_values(\array_filter((array) $this->config('origins_qa.settings')->get('node_delete_bundle_order')));

    if ($ids === []) {
      return $ids;
    }

    $positions = \array_flip($order);
    $unlisted = \count($positions);

    // Look up the bundle of each node so the IDs can be sorted.
    $bundles = [];
    $storage = $this->entityTypeManager->getStorage('node');
    foreach (\array_chunk($ids, 500) as $chunk) {
      $bundle_ids = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->condition('nid', $chunk, 'IN')
        ->groupBy('nid')
        ->groupBy('type')
        ->execute();
      foreach ($bundle_ids as $row) {
        $bundles[(int) $row['nid']] = (string) $row['type'];
      }
    }

    // Sort by configured bundle position, then newest first within a bundle
    // so that nested content of the same bundle (e.g. a child subtopic) is
    // deleted before the parent that it was created under.
    \usort($ids, static function (int $a, int $b) use ($bundles, $positions, $unlisted): int {
      $position_a = $positions[$bundles[$a] ?? ''] ?? $unlisted;
      $position_b = $positions[$bundles[$b] ?? ''] ?? $unlisted;
      return [$position_a, $b] <=> [$position_b, $a];
    });

    return $ids;
  }

}
