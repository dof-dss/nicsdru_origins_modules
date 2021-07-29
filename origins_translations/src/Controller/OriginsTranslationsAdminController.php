<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Origins Translations routes.
 */
class OriginsTranslationsAdminController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Builds the response.
   */
  public function toggle() {
    $lang_code = $this->routeMatch->getParameter('code');

    $languages = $this->config('origins_translations.languages')->getRawData();

    $languages[$lang_code][1] = !$languages[$lang_code][1];

    $this->configFactory->getEditable('origins_translations.languages')->setData($languages)->save();

    return $this->redirect('origins_translations.settings.languages');

  }

}
