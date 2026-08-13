<?php

namespace Drupal\origins_book;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Invalidates caches affected by book outline membership changes.
 */
class BookCacheInvalidator {

  /**
   * Constructs a book cache invalidator.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheTagsInvalidator) {}

  /**
   * Invalidates caches affected by one book outline change.
   *
   * @param array|null $before
   *   The persisted book link before the change, or NULL for an insertion.
   * @param array|null $after
   *   The persisted book link after the change, or NULL for a removal.
   */
  public function invalidateChange(?array $before, ?array $after): void {
    $this->invalidateChanges([
      [
        'before' => $before,
        'after' => $after,
      ],
    ]);
  }

  /**
   * Invalidates caches affected by a batch of book outline changes.
   *
   * @param iterable $changes
   *   Before/after pairs keyed by `before` and `after`.
   */
  public function invalidateChanges(iterable $changes): void {
    $cache_tags = [];

    foreach ($changes as $change) {
      if (!is_array($change)) {
        continue;
      }

      $before = is_array($change['before'] ?? NULL) ? $change['before'] : [];
      $after = is_array($change['after'] ?? NULL) ? $change['after'] : [];

      if (!$this->outlineChanged($before, $after)) {
        continue;
      }

      foreach ($this->getCacheTags([$before, $after]) as $cache_tag) {
        $cache_tags[$cache_tag] = TRUE;
      }
    }

    if ($cache_tags !== []) {
      $this->cacheTagsInvalidator->invalidateTags(array_keys($cache_tags));
    }
  }

  /**
   * Gets cache tags represented by the supplied book links.
   *
   * @param array $book_links
   *   A list of book-link data containing book, node, and parent IDs.
   *
   * @return string[]
   *   Valid, deduplicated book and node cache tags.
   */
  public function getCacheTags(array $book_links): array {
    $cache_tags = [];

    foreach ($book_links as $book_link) {
      $nid = $this->positiveId($book_link['nid'] ?? NULL);
      $bid_value = $book_link['bid'] ?? NULL;
      $bid = $bid_value === 'new' ? $nid : $this->positiveId($bid_value);
      $pid = $this->positiveId($book_link['pid'] ?? NULL);

      if ($bid !== NULL) {
        $cache_tags['bid:' . $bid] = TRUE;
      }
      if ($nid !== NULL) {
        $cache_tags['node:' . $nid] = TRUE;
      }
      if ($pid !== NULL) {
        $cache_tags['node:' . $pid] = TRUE;
      }
    }

    return array_keys($cache_tags);
  }

  /**
   * Checks whether a node joined, left, moved, or changed order in an outline.
   */
  private function outlineChanged(array $before, array $after): bool {
    return $this->outlinePosition($before) !== $this->outlinePosition($after);
  }

  /**
   * Gets the normalized book position which affects navigation.
   */
  private function outlinePosition(array $book_link): array {
    if ($book_link === []) {
      return [];
    }

    $nid = $this->positiveId($book_link['nid'] ?? NULL);
    $bid_value = $book_link['bid'] ?? NULL;

    return [
      'bid' => $bid_value === 'new' ? $nid : $this->positiveId($bid_value),
      'pid' => $this->positiveId($book_link['pid'] ?? NULL),
      'weight' => is_numeric($book_link['weight'] ?? NULL) ? (int) $book_link['weight'] : 0,
    ];
  }

  /**
   * Normalizes a positive numeric ID.
   */
  private function positiveId(mixed $value): ?int {
    return is_numeric($value) && (int) $value > 0 ? (int) $value : NULL;
  }

}
