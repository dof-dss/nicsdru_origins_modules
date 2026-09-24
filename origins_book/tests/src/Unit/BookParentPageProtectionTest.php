<?php

namespace Drupal\Tests\origins_book\Unit;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\origins_book\BookParentPageProtection;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;

/**
 * Tests the policy which protects book pages with children.
 *
 * @group origins_book
 */
class BookParentPageProtectionTest extends UnitTestCase {

  /**
   * Tests the parent-page protection decision.
   *
   * @dataProvider protectionProvider
   */
  public function testIsProtected(array $book_link, bool $has_override, bool $expected): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->expects($this->once())
      ->method('loadBookLink')
      ->with(42, FALSE)
      ->willReturn($book_link);

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->once())
      ->method('hasPermission')
      ->with(BookParentPageProtection::OVERRIDE_PERMISSION)
      ->willReturn($has_override);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(42);

    $protection = new BookParentPageProtection($book_manager);
    $this->assertSame($expected, $protection->isProtected($node, $account));
  }

  /**
   * Provides book-outline scenarios for parent-page protection.
   */
  public static function protectionProvider(): array {
    return [
      'book root with children' => [
        ['nid' => 42, 'bid' => 42, 'pid' => 0, 'has_children' => 1],
        FALSE,
        TRUE,
      ],
      'nested book parent with children' => [
        ['nid' => 42, 'bid' => 10, 'pid' => 20, 'has_children' => 1],
        FALSE,
        TRUE,
      ],
      'book page without children' => [
        ['nid' => 42, 'bid' => 10, 'pid' => 20, 'has_children' => 0],
        FALSE,
        FALSE,
      ],
      'node outside a book' => [
        [],
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Tests that the override avoids the book lookup and protection.
   */
  public function testOverridePermissionAllowsParentChange(): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->expects($this->never())->method('loadBookLink');

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->once())
      ->method('hasPermission')
      ->with(BookParentPageProtection::OVERRIDE_PERMISSION)
      ->willReturn(TRUE);

    $node = $this->createMock(NodeInterface::class);
    $protection = new BookParentPageProtection($book_manager);
    $this->assertFalse($protection->isProtected($node, $account));
  }

  /**
   * Tests that an unsaved node cannot yet be a book parent.
   */
  public function testNewNodeIsNotProtected(): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->expects($this->never())->method('loadBookLink');

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())->method('hasPermission');

    $node = $this->createMock(NodeInterface::class);
    $node->method('isNew')->willReturn(TRUE);

    $protection = new BookParentPageProtection($book_manager);
    $this->assertFalse($protection->isProtected($node, $account));
  }

  /**
   * Tests which moderation changes are blocked for a protected parent.
   *
   * @dataProvider archiveProvider
   */
  public function testArchiveProtection(string $new_state, ?string $original_state, bool $expected): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->method('loadBookLink')->willReturn(['has_children' => 1]);

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(42);

    $protection = new BookParentPageProtection($book_manager);
    $this->assertSame($expected, $protection->isArchiveBlocked($node, $account, $new_state, $original_state));
  }

  /**
   * Provides moderation-state scenarios.
   */
  public static function archiveProvider(): array {
    return [
      'new archive transition' => ['archived', 'published', TRUE],
      'already archived page save' => ['archived', 'archived', FALSE],
      'publish transition' => ['published', 'draft', FALSE],
      'submit for review transition' => ['needs_review', 'draft', FALSE],
    ];
  }

  /**
   * Tests that every transition to archived is hidden for a protected parent.
   */
  public function testBlockedArchiveTransitionIds(): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->method('loadBookLink')->willReturn(['has_children' => 1]);

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(42);

    $archive_state = $this->createMock(StateInterface::class);
    $archive_state->method('id')->willReturn('archived');
    $published_state = $this->createMock(StateInterface::class);
    $published_state->method('id')->willReturn('published');

    $archive_transition = $this->createMock(TransitionInterface::class);
    $archive_transition->method('to')->willReturn($archive_state);
    $other_archive_transition = $this->createMock(TransitionInterface::class);
    $other_archive_transition->method('to')->willReturn($archive_state);
    $publish_transition = $this->createMock(TransitionInterface::class);
    $publish_transition->method('to')->willReturn($published_state);

    $protection = new BookParentPageProtection($book_manager);
    $this->assertSame([
      'published_archived',
      'needs_review_archived',
    ], $protection->getBlockedArchiveTransitionIds($node, $account, [
      'published_archived' => $archive_transition,
      'publish' => $publish_transition,
      'needs_review_archived' => $other_archive_transition,
    ]));
  }

  /**
   * Tests that archive transitions remain available for an exempt account.
   */
  public function testArchiveTransitionRemainsForOverridePermission(): void {
    $book_manager = $this->createMock(BookManagerInterface::class);
    $book_manager->expects($this->never())->method('loadBookLink');

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(TRUE);

    $node = $this->createMock(NodeInterface::class);
    $transition = $this->createMock(TransitionInterface::class);
    $transition->expects($this->never())->method('to');

    $protection = new BookParentPageProtection($book_manager);
    $this->assertSame([], $protection->getBlockedArchiveTransitionIds($node, $account, [
      'published_archived' => $transition,
    ]));
  }

}
