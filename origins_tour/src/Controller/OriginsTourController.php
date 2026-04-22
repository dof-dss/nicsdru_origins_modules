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
    return [
      '#theme' => 'help_homepage',
    ];
  }

}
