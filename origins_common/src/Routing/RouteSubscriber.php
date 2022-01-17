<?php

namespace Drupal\origins_common\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber for taxonomy.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $route->setRequirement('_permission', 'administer taxonomy');
    }
  }

}
