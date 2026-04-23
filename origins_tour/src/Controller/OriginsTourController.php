<?php

declare(strict_types=1);

namespace Drupal\origins_tour\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Origins help pages.
 */
final class OriginsTourController extends ControllerBase {

  /**
   * Returns the help homepage render array.
   */
  public function __invoke(): array {
    $tour_links = [];

    $tours = $this->entityTypeManager()->getStorage('tour')->loadMultiple();
    foreach ($tours as $tour) {

      // We only want to display Origins tours in our help section.
      if (substr($tour->id(), 0, 8) === 'origins_') {
        $tour_links[] = [
          '#type' => 'link',
          '#title' => $tour->label(),
          '#url' => $tour->toUrl(),
        ];
      }
    }

    return [
      'homepage' => [
        '#theme' => 'help_homepage',
      ],
      'tours' => [
        '#theme' => 'tours',
        '#tours' => $tour_links
      ],
    ];
  }

}
