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
      '#type' => 'processed_text',
      '#text' => $this->config('origins_translations.settings')->get('content')['value'],
      '#format' => $this->config('origins_translations.settings')->get('content')['format'],
    ];

    return $build;
  }

  /**
   * Title callback.
   */
  public function getTitle() {
    return $this->config('origins_translations.settings')->get('title');
  }

}
