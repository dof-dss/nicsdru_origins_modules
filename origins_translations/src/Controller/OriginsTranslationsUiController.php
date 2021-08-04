<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Origins Translations routes.
 */
class OriginsTranslationsUiController extends ControllerBase {
  /**
   * Returns a translated title if present in the configuration.
   */
  public function title(Request $request) {
    $response = new Response();

    $config = $this->config('origins_translations.languages');
    $languages = $config->getRawData();
    $code = $request->get('code');

    if (array_key_exists($code, $languages)) {
      return $response->setContent($languages[$code][2]);
    }
    else {
      return $response->setContent('Translate this page');
    }
  }

}
