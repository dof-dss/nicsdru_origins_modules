<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Origins Translations routes.
 */
class OriginsTranslationsUiController extends ControllerBase {

  /**
   * Builds the AJAX response.
   */
  public function build(Request $request) {

    if (!$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }

    $response = new AjaxResponse();

    $selector = '.ajax-wrapper';
    $content = 'list of languages';

    $languages = [
      'Afrikaans' => 'af',
      'Albanian' => 'sq',
      'Arabic' => 'ar',
      'Armenian' => 'hy',
    ];

    $url = $request->getQueryString();

    foreach ($languages as $language => $code) {
      $translations['https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' . $code . '&u=' . $url] = $language;
    }

    $content = [
      '#type' => 'select',
      '#title' => $this->t('Select language'),
      '#options' => $translations
    ];

    $response->addCommand(new ReplaceCommand($selector, $content, []));

    return $response;
  }

}
