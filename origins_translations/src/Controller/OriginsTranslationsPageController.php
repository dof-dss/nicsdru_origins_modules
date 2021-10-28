<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Origins Translations page.
 */
class OriginsTranslationsPageController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    // Redirect to a custom URL if enabled in the translation page settings.
    if ($this->config('origins_translations.settings')->get('override_default_route')) {
      $url = $this->config('origins_translations.settings')->get('override_url');
      return new RedirectResponse($url);
    }

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
