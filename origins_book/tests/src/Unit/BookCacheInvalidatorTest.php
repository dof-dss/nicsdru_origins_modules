<?php

namespace Drupal\Tests\origins_book\Unit;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\origins_book\BookCacheInvalidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests cache invalidation for book outline changes.
 *
 * @group origins_book
 */
class BookCacheInvalidatorTest extends UnitTestCase {

  /**
   * Tests that old and new book, node, and parent tags are invalidated once.
   */
  public function testInvalidatesAffectedBookLinks(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'bid:10',
        'node:84',
        'node:21',
        'bid:20',
        'node:42',
        'node:85',
      ]);

    $invalidator = new BookCacheInvalidator($cache_tags_invalidator);
    $invalidator->invalidateChanges([
      [
        'before' => ['nid' => 84, 'bid' => 10, 'pid' => 21],
        'after' => ['nid' => 84, 'bid' => 20, 'pid' => 42],
      ],
      [
        'before' => ['nid' => 85, 'bid' => 10, 'pid' => 21],
        'after' => ['nid' => 85, 'bid' => 20, 'pid' => 42],
      ],
    ]);
  }

  /**
   * Tests insertion and removal outline changes.
   */
  public function testInvalidatesInsertionAndRemoval(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'bid:10',
        'node:84',
        'node:21',
        'node:85',
      ]);

    $invalidator = new BookCacheInvalidator($cache_tags_invalidator);
    $invalidator->invalidateChanges([
      [
        'before' => NULL,
        'after' => ['nid' => 84, 'bid' => 10, 'pid' => 21],
      ],
      [
        'before' => ['nid' => 85, 'bid' => 10, 'pid' => 21],
        'after' => NULL,
      ],
    ]);
  }

  /**
   * Tests that unchanged outline data does not invalidate tags.
   */
  public function testIgnoresUnchangedOutlineData(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator
      ->expects($this->never())
      ->method('invalidateTags');

    $invalidator = new BookCacheInvalidator($cache_tags_invalidator);
    $invalidator->invalidateChanges([
      [
        'before' => ['nid' => 84, 'bid' => 10, 'pid' => 21, 'weight' => 5],
        'after' => ['nid' => 84, 'bid' => 10, 'pid' => 21, 'weight' => 5],
      ],
      [
        'before' => NULL,
        'after' => NULL,
      ],
      'invalid change',
    ]);
  }

  /**
   * Tests that changing a page's weight invalidates its navigation caches.
   */
  public function testInvalidatesWeightChanges(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'bid:10',
        'node:84',
        'node:21',
      ]);

    $invalidator = new BookCacheInvalidator($cache_tags_invalidator);
    $invalidator->invalidateChange(
      ['nid' => 84, 'bid' => 10, 'pid' => 21, 'weight' => 0],
      ['nid' => 84, 'bid' => 10, 'pid' => 21, 'weight' => 5],
    );
  }

  /**
   * Tests cache-tag normalization for roots and newly created books.
   */
  public function testGetsNormalizedCacheTags(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator = new BookCacheInvalidator($cache_tags_invalidator);

    $this->assertSame([
      'bid:42',
      'node:42',
      'bid:10',
      'node:21',
    ], $invalidator->getCacheTags([
      ['nid' => '42', 'bid' => 'new', 'pid' => 0],
      ['nid' => 42, 'bid' => 42, 'pid' => 0],
      ['nid' => 21, 'bid' => 10, 'pid' => -1],
      ['nid' => 'invalid', 'bid' => NULL, 'pid' => NULL],
    ]));
  }

}
