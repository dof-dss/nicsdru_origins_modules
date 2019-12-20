<?php

namespace Drupal\origins_taxonomy_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Override from base class to set lighter
   * event priority; execute after taxonomy_access_fix
   * RouteSubscriber callbacks.
   *
   * See https://www.drupal.org/docs/8/creating-custom-modules/subscribe-to-and-dispatch-events#s-event-subscriber-priorities.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /* ==== Adjust route parameters for: Taxonomy manager module. ===================
     * Taxonomy manager isn't very precise with mapping its own routes to permissions
     * so here we use taxonomy_access_fix's granular defined permissions to map onto
     * the routes from taxonomy_manager.
     *
     * It's not a perfect fit, so AJAX routes like search/autocomplete map to a more
     * general 'edit' permission for relative safety.
     */
    $vocab_routes_to_alter = [
      'taxonomy_manager.admin_vocabulary',
      'taxonomy_manager.admin_vocabulary.add',
      'taxonomy_manager.admin_vocabulary.move',
      'taxonomy_manager.admin_vocabulary.search',
      'taxonomy_manager.admin_vocabulary.delete',
      'taxonomy_manager.admin_vocabulary.searchautocomplete',
    ];

    foreach ($vocab_routes_to_alter as $route_id) {
      $route = $collection->get($route_id);
      $route->setRequirements([
        '_custom_access' => '\Drupal\origins_taxonomy_access\TaxonomyVocabAccess::handleAccess',
      ]);
      $route->setOption('_admin_route', TRUE);

      $route_elements = explode('.', $route_id);
      $op = end($route_elements);

      // Set an operation option for us to latch onto in the handler class, unless it's the vocab overview route.
      if ($op != 'admin_vocabulary') {
        $route->setOption('op', $op);
      }
    }

    // Amend overview and subtree route access to be more relaxed; map to the core taxonomy overview permission.
    $general_routes = [
      'taxonomy_manager.admin',
      'taxonomy_manager.subTree',
    ];

    foreach ($general_routes as $route_id) {
      $route = $collection->get($route_id);
      $route->setRequirement('_permission', 'access taxonomy overview');
    }

    // === Adjust route parameters for: Taxonomy core module (after taxonomy_access_fix has applied changes). ===
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      // Replace custom access callback with standard high level permission so core's taxonomy module's
      // menu items don't leave a stub im the 'Structure' menu making for a confusing non-admin editor experience.
      $route->setRequirements([
        '_permission' => 'administer taxonomy',
      ]);
      // Clear any existing route options.
      $route->setOptions([]);
    }
  }

}
