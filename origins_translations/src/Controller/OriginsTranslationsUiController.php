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
    $languages = [
      'Afrikaans' => 'af',
      'Albanian' => 'sq',
      'Arabic' => 'ar',
      'Armenian' => 'hy',
    ];

    $url = $request->query->get('url');

    foreach ($languages as $language => $code) {
      $translations['https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' . $code . '&u=' . $url] = $language;
    }

    $content['language_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Select language'),
      '#options' => $translations,
      '#attributes' => ['class' => ['origins-translation']],
    ];

    $content['#attached']['library'][] = 'origins_translations/origins_translations.link_ui';


    // TODO: Add cache context for URLs

    $response->addCommand(new ReplaceCommand($selector, $content, []));

    return $response;
  }

}
