<?php

declare(strict_types=1);

namespace Drupal\origins_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Controller for Origins help pages.
 */
final class HelpPagesController extends ControllerBase {

  public function __construct(
    private readonly RouteProviderInterface $routeProvider,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('router.route_provider'),
    );
  }

  /**
   * Returns the help page render array.
   */
  public function __invoke(): array {
    $tour_links = [];

    $tours = $this->entityTypeManager()->getStorage('tour')->loadMultiple();
    foreach ($tours as $tour) {

      // We only want to display Origins tours in our help section.
      if (!str_starts_with($tour->id(), 'origins_')) {
        continue;
      }

      $route_name = $tour->getRoutes()[0]['route_name'];

      try {
        $route = $this->routeProvider->getRouteByName($route_name);
      }
      catch (RouteNotFoundException) {
        continue;
      }

      // If a route requires parameters (e.g. node.add) render it as a text,
      // else render a link to the page.
      $required_params = array_diff(
        $route->compile()->getVariables(),
        array_keys($route->getDefaults())
      );

      if (!empty($required_params)) {
        $tour_links[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $tour->label(),
        ];
        continue;
      }

      $tour_links[] = [
        '#type' => 'link',
        '#title' => $tour->label(),
        // Have the tour automatically start when the url is clicked.
        '#url' => Url::fromRoute($route_name, [], ['absolute' => TRUE, 'query' => ['tour' => 1]]),
      ];
    }

    return [
      'homepage' => [
        '#theme' => 'help_homepage',
      ],
      'tours' => [
        '#theme' => 'tours',
        '#tours' => $tour_links,
      ],
    ];
  }

}
