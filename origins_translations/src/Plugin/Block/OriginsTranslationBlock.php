<?php

namespace Drupal\origins_translations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a link to translations for the current URL.
 *
 * @Block(
 *   id = "origins_translations_block",
 *   admin_label = @Translation("Origins Translation"),
 *   category = @Translation("Origins")
 * )
 */
class OriginsTranslationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Config object for Origins Translations.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('origins_translations.settings');
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest(),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {

    $title = $this->t('Translate this page');
    $domain = $this->config->get('domain');

    if (empty($domain)) {
      $url = $this->request->getUri();
    } else {
      $url = $domain . $this->request->getPathInfo();
    }

    $build['link'] = [
      '#title' => $title,
      '#type' => 'link',
      '#url' => \Drupal\Core\Url::fromRoute('origins_translations.translation-link-ui', ['url' => $url]),
      '#attributes' => ['class' => ['origins-translation-link', 'use-ajax']],
      '#attached' => ['library' => ['origins_translations/origins_translations.link_ui']]
    ];

    $build['ajax-wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['ajax-wrapper']],
    ];

    return $build;
  }

}
