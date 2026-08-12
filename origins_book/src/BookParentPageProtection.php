<?php

namespace Drupal\origins_book;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\workflows\TransitionInterface;

/**
 * Determines whether a book parent may be archived or deleted.
 */
class BookParentPageProtection {

  /**
   * Permission which allows the book parent safeguards to be overridden.
   */
  public const OVERRIDE_PERMISSION = 'override book parent protection';

  /**
   * Constructs the book parent page protection service.
   */
  public function __construct(protected BookManagerInterface $bookManager) {}

  /**
   * Checks whether the account is prevented from changing the parent page.
   */
  public function isProtected(NodeInterface $node, AccountInterface $account): bool {
    if ($node->isNew() || $account->hasPermission(self::OVERRIDE_PERMISSION)) {
      return FALSE;
    }

    $book_link = $this->bookManager->loadBookLink($node->id(), FALSE);
    return !empty($book_link['has_children']);
  }

  /**
   * Checks whether a new moderation state represents a blocked archive.
   */
  public function isArchiveBlocked(NodeInterface $node, AccountInterface $account, string $new_state, ?string $original_state = NULL): bool {
    return $new_state === 'archived'
      && $original_state !== 'archived'
      && $this->isProtected($node, $account);
  }

  /**
   * Gets archive transitions which must be hidden for a protected parent page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The page being shown in the moderation sidebar.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account viewing the moderation sidebar.
   * @param \Drupal\workflows\TransitionInterface[] $transitions
   *   The transitions available to the current user, keyed by transition ID.
   *
   * @return string[]
   *   The transition IDs which lead to the archived state.
   */
  public function getBlockedArchiveTransitionIds(NodeInterface $node, AccountInterface $account, array $transitions): array {
    if (!$this->isProtected($node, $account)) {
      return [];
    }

    $blocked_transition_ids = [];

    // The array is keyed by transition ID. Examine each transition's
    // destination state and retain the key only when it archives the page.
    foreach ($transitions as $transition_id => $transition) {
      assert($transition instanceof TransitionInterface);

      $destination_state = $transition->to();
      $destination_state_id = $destination_state->id();

      if ($destination_state_id !== 'archived') {
        continue;
      }

      $blocked_transition_ids[] = $transition_id;
    }

    return $blocked_transition_ids;
  }

}
