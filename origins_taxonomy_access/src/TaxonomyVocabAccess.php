<?php

namespace Drupal\origins_taxonomy_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\Routing\Route;

class TaxonomyVocabAccess {

  /**
   * Access callback for common CUSTOM taxonomy operations.
   */
  public static function handleAccess(Route $route, RouteMatchInterface $match) {
    $op = $route->getOption('op');
    $taxonomy_vocabulary = $match->getParameter('taxonomy_vocabulary');

    // Admin: always.
    if (\Drupal::currentUser()->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }
    else {
      // Check user can view terms in this vocab.
      if ($match->getRouteName() == 'taxonomy_manager.admin_vocabulary') {
        return AccessResult::allowedIfHasPermission(\Drupal::currentUser(), 'view terms in ' . $taxonomy_vocabulary->id());
      }

      // Loosely map these outlying tasks to a general permission for the vocab.
      if (in_array($op, ['search', 'searchautocomplete'])) {
        return AccessResult::allowedIfHasPermission(\Drupal::currentUser(), 'edit terms in ' . $taxonomy_vocabulary->id());
      }

      // Move == reorder; difference in semantics between taxonomy_manager and taxonomy_access_fix.
      if ($op == 'move') {
        $op = 'reorder';
      }

      // Check permissions for add, delete, reorder defined by taxonomy_access_fix; defined per vocab.
      if (\Drupal::currentUser()->hasPermission($op . ' terms in ' . $taxonomy_vocabulary->id())) {
        return AccessResult::allowed();
      }
    }

    // Final fallback: return access denied.
    return AccessResult::forbidden();
  }

}
