<?php

declare(strict_types=1);

namespace Drupal\origins_reporter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the report entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ReportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view origins_reporter_report'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit origins_reporter_report'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete origins_reporter_report'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete origins_reporter_report revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view origins_reporter_report revision', 'view origins_reporter_report']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert origins_reporter_report revision', 'edit origins_reporter_report']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create origins_reporter_report', 'administer origins_reporter_report'], 'OR');
  }

}
