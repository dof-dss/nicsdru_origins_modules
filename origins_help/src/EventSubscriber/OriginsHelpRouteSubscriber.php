<?php

declare(strict_types=1);

namespace Drupal\origins_help\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class OriginsHelpRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Override Core's help section with our own as the core page
    // displays help sections for modules and this page is intended
    // for end-users.
    if ($route = $collection->get('help.main')) {
      $route->setDefault('_controller', '\Drupal\origins_help\Controller\HelpPagesController');
    }
  }

}
