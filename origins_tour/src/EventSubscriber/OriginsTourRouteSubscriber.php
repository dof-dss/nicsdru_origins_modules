<?php

declare(strict_types=1);

namespace Drupal\origins_tour\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class OriginsTourRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Override Core's help section with our own.
    if ($route = $collection->get('help.main')) {
      $route->setDefault('_controller', '\Drupal\origins_tour\Controller\HelpPagesController');
    }
  }

}
