<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\origins_translations\Utilities;
use Kint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a form to select a translation.
 */
class LanguageSelectorForm extends FormBase {

  /**
   * Origins Translation utilities.
   *
   * @var \Drupal\origins_translations\Utilities
   */
  protected $utilities;

  /**
   * Wrapper id for Ajax replacement.
   *
   * @var string
   */
  protected string $language_selector_wrapper_id = 'origins-translation-select-wrapper';

  /**
   * The form constructor.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_translation_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();
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

    $form['translations-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['origins-translation-container'],
        'role' => 'menu',
      ],
    ];

    // Provide a link to the translations page for when the browser doesn't
    // have Javascript enabled. This will be hidden if JS is enabled.
    $form['translations-container']['translations-link'] = [
      '#type' => 'link',
      '#title' => $this->t('Translate this page'),
      '#url' => Url::fromRoute('origins_translations.translations-page', ['url' => $url]),
      '#attributes' => ['class' => ['origins-translation-link']],
    ];

    // Provide a button to show the list of translation links.
    // The button is hidden and enabled with JS.
    $form['translations-container']['translations-button'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Translate this page'),
      '#attributes' => [
        'class' => ['origins-translation-button', 'hidden'],
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
      ],
      '#attached' => ['library' => ['origins_translations/origins_translations.link_ui']],
    ];

    $form['translations-container']['translations-menu'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['origins-translation-menu'],
        'role' => 'menu',
      ],
    ];

    // List of translation links.
    $translation_list_id = Html::getUniqueId('origins-translation-links');

    $form['translations-container']['translations-menu']['translation-list'] = [
      '#theme' => 'item_list__origins_translation_list',
      '#list_type' => 'ul',
      '#title' => 'Select a language',
      '#items' => $translation_links,
      '#attributes' => [
        'id' => $translation_list_id,
        'class' => ['origins-translation-list'],
        'aria-label' => 'submenu',
        '#wrapper_attributes' => ['id', $this->language_selector_wrapper_id],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Method stub to comply with interface.
  }

}
