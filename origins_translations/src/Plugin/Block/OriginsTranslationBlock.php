<?php

namespace Drupal\origins_translations\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\origins_translations\Utilities;
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
   * Origins Translation utilities.
   *
   * @var \Drupal\origins_translations\Utilities
   */
  protected $utilities;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

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
   * @param \Drupal\origins_translations\Utilities $utilities
   *   Origins Translations utilities service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Utilities $utilities, Request $request, FormBuilder $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('origins_translations.settings');
    $this->utilities = $utilities;
    $this->request = $request;
    $this->formBuilder = $form_builder;
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
      $container->get('origins_translations.utilities'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $request = $this->request;
    $languages = $this->utilities->getActiveLanguages();

    // Determine the URL to be translated.
    $url = $request->getBaseUrl();

    // Create links to Google Translate.
    $translation_links = [];
    foreach ($languages as $code => $language) {
      $link_text = Markup::create($language[0] . ' &mdash; <span lang="' . $code . '" dir="' . $language[5] . '">' . $language[1] . '</span>');
      $link_url = Url::fromUri('https://translate.google.com/translate', ['query' => [
        'hl' => 'en',
        'tab' => 'TT',
        'sl' => 'auto',
        'tl' => $code,
        'u' => $url,
      ]]);
      $translation_links[] = \Drupal\Core\Link::fromTextAndUrl($link_text, $link_url);
    }

    $build = [];

    $build['translations-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['origins-translation-container'],
      ],
      '#attached' => ['library' => ['origins_translations/origins_translations.link_ui']],
    ];

    // Provide a link to the translations page for when the browser doesn't
    // have Javascript enabled. This will be hidden if JS is enabled.
    $build['translations-container']['translations-link'] = [
      '#type' => 'link',
      '#title' => $this->t('Translate this page'),
      '#url' => Url::fromRoute('origins_translations.translations-page', ['url' => $url]),
      '#attributes' => ['class' => ['origins-translation-link']],
    ];

    // Provide a button to show the list of translation links.
    // The button is hidden and enabled with JS.
    $build['translations-container']['translations-button'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Translate this page'),
      '#attributes' => [
        'class' => ['origins-translation-button', 'hidden'],
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
      ],
    ];

    $build['translations-container']['translations-menu'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['origins-translation-menu'],
        'role' => 'menu',
      ],
    ];

    // List of translation links.
    $translation_list_id = Html::getUniqueId('origins-translation-links');

    $build['translations-container']['translations-menu']['translation-list'] = [
      '#theme' => 'item_list__origins_translation_list',
      '#list_type' => 'ul',
      '#title' => 'Select a language',
      '#items' => $translation_links,
      '#attributes' => [
        'id' => $translation_list_id,
        'class' => ['origins-translation-list'],
        'aria-label' => 'submenu',
      ],
    ];

    return $build;
  }

}
