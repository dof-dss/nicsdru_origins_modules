<?php declare(strict_types = 1);

namespace Drupal\origins_add_content\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for Add Content.
 */
final class ContentAddRouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a ContentAddRouteSubscriber object.
   */
  public function __construct(
    private readonly ControllerResolverInterface $controllerResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // @see https://www.drupal.org/node/2187643

    if ($route = $collection->get('node.add_page')) {
      $route->setDefault('_controller', '\Drupal\origins_add_content\Controller\AddContentPageController::addContentList');
    }
  }

}
