<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
      $translations_page = Url::fromRoute('origins_translations.translations-page');
      $response = new RedirectResponse($translations_page->toString());
      $response->send();
      var_dump('FOO');
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
