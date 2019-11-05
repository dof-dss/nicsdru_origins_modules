<?php

namespace Drupal\origins_editor_responsive_images\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    if ($route = $collection->get('editor.image_dialog')) {
      $route->setDefaults(['_form' => '\Drupal\origins_editor_responsive_images\Form\ResponsiveEditorImageDialog']);
    }
  }

}
