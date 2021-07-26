<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Origins Translations page.
 */
class OriginsTranslationsPageController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#markup' => $this->t('Translations page'),
    ];

    return $build;
  }

}
