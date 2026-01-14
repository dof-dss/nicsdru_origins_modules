<?php

namespace Drupal\origins_content_issue;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ContentIssueCommentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view content issue')) {
          return AccessResult::allowed();
        }
        break;

      case 'update':
        if ($account->hasPermission('edit any content issue')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit own content issue') &&
          // @phpstan-ignore-next-line
          $account->id() === $entity->getOwnerId()) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit assigned content issue') &&
          // @phpstan-ignore-next-line
          $account->id() === $entity->get('assigned_to')->target_id) {
          return AccessResult::allowed();
        }
        break;

      case 'delete':
        if ($account->hasPermission('delete any content issue')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('delete own content issue') &&
          // @phpstan-ignore-next-line
          $account->id() === $entity->getOwnerId()) {
          return AccessResult::allowed();
        }
        break;
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create content issue');
  }

}
