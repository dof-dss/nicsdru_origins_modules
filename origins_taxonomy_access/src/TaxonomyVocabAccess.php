<?php

namespace Drupal\origins_taxonomy_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\Routing\Route;

/**
 * Validates permissions for users against taxonomy vocabularies and operations on them.
 *
 * @package Drupal\origins_taxonomy_access
 */
class TaxonomyVocabAccess {

  /**
   * Access callback for common CUSTOM taxonomy operations.
   *
   * We generally DO want to allow anonymous users access to view term pages.
   * We can't re-use that permission for taxonomy manager routes, because it would allow
   * anonymous users the ability to view those routes/inspect the term hierarchy and potentially manipulate
   * taxonomy data which would be a Bad Thing (TM).
   *
   * So we're predicating this approach by saying something like:
   * - If you can administer all things taxonomy (aka: Lord Of The Terms), then grant access early.
   * - If you can't view the admin pages (aka: Anonymous User), then reject early.
   * - Otherwise, evaluate what you can/cannot do in more detail.
   */
  public static function handleAccess(Route $route, RouteMatchInterface $match) {
    $op = $route->getOption('op');
    $taxonomy_vocabulary = $match->getParameter('taxonomy_vocabulary');
    $current_user = \Drupal::currentUser();

    // Admin: always.
    if ($current_user->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }
    else {
      // If the user can't view admin pages, reject the request early.
      if ($current_user->hasPermission('access administration pages') == FALSE) {
        return AccessResult::forbidden();
      }

      // Check user can view terms in this vocab.
      if ($match->getRouteName() == 'taxonomy_manager.admin_vocabulary') {
        return AccessResult::allowedIfHasPermission($current_user, 'view terms in ' . $taxonomy_vocabulary->id());
      }

      // Loosely map these outlying tasks to a general permission for the vocab.
      if (in_array($op, ['search', 'searchautocomplete'])) {
        return AccessResult::allowedIfHasPermission($current_user, 'edit terms in ' . $taxonomy_vocabulary->id());
      }

      // Move == reorder; difference in semantics between taxonomy_manager and taxonomy_access_fix.
      if ($op == 'move') {
        $op = 'reorder';
      }

      // Check permissions for add, delete, reorder defined by taxonomy_access_fix; defined per vocab.
      if ($current_user->hasPermission($op . ' terms in ' . $taxonomy_vocabulary->id())) {
        return AccessResult::allowed();
      }
    }

    // Final fallback: return access denied.
    return AccessResult::forbidden();
  }

}
