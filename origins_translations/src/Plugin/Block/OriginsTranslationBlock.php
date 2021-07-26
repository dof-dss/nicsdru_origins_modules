<?php

namespace Drupal\origins_translations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,  ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('origins_translations.settings');
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
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {

    $domain = $this->config->get('domain');

    if (empty($domain)) {
      $url = \Drupal::request()->getUri();
    } else {
      $url = $domain . \Drupal::request()->getPathInfo();
    }

    $build['content'] = [
      '#markup' => $this->t('<a class="use-ajax" href="/origins-translations/translation-link-ui?url=' . $url . '">Translate this page</a> <div class="ajax-wrapper"></div>'),
    ];
    return $build;
  }

}
