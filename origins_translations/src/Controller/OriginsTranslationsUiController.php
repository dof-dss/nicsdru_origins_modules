<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Origins Translations routes.
 */
class OriginsTranslationsUiController extends ControllerBase {

  /**
   * Builds the AJAX response.
   */
  public function selectLanguage(Request $request) {

    if (!$request->isXmlHttpRequest()) {
      $translations_page = Url::fromRoute('origins_translations.translations-page');
      $response = new RedirectResponse($translations_page->toString());
      $response->send();
    }

    $response = new AjaxResponse();

    $selector = '.ajax-wrapper';
    $languages = $this->getActiveLanguages();
    $url = $request->query->get('url');
    $code = substr($request->headers->get('accept-language'), 0, 2);

    if (array_key_exists($code, $languages) && strpos($code, 'en') !== 0) {
      $translations[''] = $languages[$code][3];
    } else {
      $translations[''] = 'Select a language';
    }

    foreach ($languages as $code => $language) {
      $translations['https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' . $code . '&u=' . $url] = $language[0];
    }

    $content['language_dropdown'] = [
      '#type' => 'select',
      '#options' =>  $translations,
      '#attributes' => ['class' => ['origins-translation']],
    ];
    $response->addCommand(new ReplaceCommand($selector, $content, []));

    return $response;
  }

  protected function getActiveLanguages() {
    $config = $this->config('origins_translations.languages');

    $languages = $config->getRawData();
    unset($languages['_core']);

    return array_filter($languages, static fn($language) => $language['1'] === TRUE);
  }

  /**
   * Returns a translated title if present in the configuration.
   *
   * @param Request $request
   * @return Response
   */
  public function title(Request $request) {
    $response = new Response();

    $config = $this->config('origins_translations.languages');
    $languages = $config->getRawData();
    $code = $request->get('code');

    if (array_key_exists($code, $languages)) {
      return $response->setContent($languages[$code][2]);
    } else {
      return $response->setContent('Translate this page');
    }
  }

}
