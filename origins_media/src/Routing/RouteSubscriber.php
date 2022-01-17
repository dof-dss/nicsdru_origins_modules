<?php

namespace Drupal\origins_media\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Re-map the entity embed form builder to our own overriding class
    // so we can specify the active step via form_state before the form
    // is built.
    // It's not possible to pre-inject that setting via a form_alter() hook or
    // any kind of #pre_render, #process or #after_build callback.
    if ($route = $collection->get('entity_embed.dialog')) {
      $route->setDefault('_form', '\Drupal\origins_media\Form\OriginsEntityEmbedDialog');
    }
  }

}
