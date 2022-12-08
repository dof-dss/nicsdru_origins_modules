<?php

namespace Drupal\origins_translations;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides Utility methods for the Origins Translations module.
 */
class Utilities {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an Utilities object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns languages from the configuration that are set to 'enabled'.
   */
  public function getActiveLanguages() {
    $config = $this->configFactory->get('origins_translations.languages');

    $languages = $config->getRawData();
    unset($languages['_core']);

    return array_filter($languages, static fn($language) => $language['2'] === TRUE);
  }

}
