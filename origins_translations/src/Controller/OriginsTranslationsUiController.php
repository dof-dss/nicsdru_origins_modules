<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\origins_translations\Utilities;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Origins Translations UI elements.
 */
class OriginsTranslationsUiController extends ControllerBase {

  /**
   * Origins Translation utilities.
   *
   * @var \Drupal\origins_translations\Utilities
   */
  protected $utilities;

  /**
   * The controller constructor.
   *
   * @param \Drupal\origins_translations\Utilities $utilities
   *   Origins Translations utilities service.
   */
  public function __construct(Utilities $utilities) {
    $this->utilities = $utilities;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('origins_translations.utilities')
    );
  }

  /**
   * Returns a translated title if present in the configuration.
   */
  public function title(Request $request) {
    $response = new Response();

    $config = $this->config('origins_translations.languages');
    $languages = $config->getRawData();
    $code = strtolower($request->get('code'));

    if (array_key_exists($code, $languages)) {
      return $response->setContent($languages[$code][3]);
    }
    else {
      return $response->setContent('Translate this page');
    }
  }

  /**
   * Returns active languages in a JSON response.
   */
  public function languages(Request $request) {
    $languages = $this->utilities->getActiveLanguages();

    return new JsonResponse($languages, 200, [], FALSE);
  }

}
