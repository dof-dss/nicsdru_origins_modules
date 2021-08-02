<?php

namespace Drupal\origins_translations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
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
   * Display a table of languages and settings for the site.
   */
  public function languages() {
    $config = $this->config('origins_translations.languages');

    $languages = $config->getRawData();
    unset($languages['_core']);

    $build['languages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Operations'),
        $this->t('Language'),
        $this->t('Enabled'),
        $this->t('Translate this page'),
        $this->t('Select a language'),
      ],
    ];

    foreach ($languages as $code => $language) {
      $build['languages']['#rows'][$code] = [
        [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('origins_translations.settings.languages.edit', ['code' => $code]),
              ],
              'toggle' => [
                'title' => $this->t('Enable/Disable'),
                'url' => Url::fromRoute('origins_translations.settings.languages.toggle', ['code' => $code]),
              ],
            ],
          ],
        ],
        $language['0'],
        ($language['1']) ? $this->t('True') : $this->t('False'),
        $language['2'],
        $language['3'],
      ];
    }

    return $build;
  }

  /**
   * Toggle method for enabling or disabling a language.
   */
  public function toggle() {
    $lang_code = $this->routeMatch->getParameter('code');

    $languages = $this->config('origins_translations.languages')->getRawData();

    $languages[$lang_code][1] = !$languages[$lang_code][1];

    $this->configFactory->getEditable('origins_translations.languages')->setData($languages)->save();

    return $this->redirect('origins_translations.settings.languages');

  }

}
