<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\origins_translations\Utilities;
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
    $url = $this->config('origins_translations.settings')->get('domain');

    // If the domain is set we need to append the current path.
    if (!empty($url)) {
      $url .= $request->getPathInfo();
    }
    else {
      $url = $request->getUri();
    }

    $wrapper_id = Html::getUniqueId('translations-select-wrapper');

    // Provide a link to the translations page for when the browser doesn't
    // have Javascript enabled. This will be hidden if JS is enabled.
    $form['translations-link'] = [
      '#type' => 'link',
      '#title' => $this->t('Translate this page'),
      '#url' => Url::fromRoute('origins_translations.translations-page', ['url' => $url]),
      '#attributes' => ['class' => ['origins-translation-link']],
    ];

    // Provide a button for AJAX callbacks when Javascript is enabled.
    // We can't bind an AJAX call to the link element above because of this bug
    // in Drupal core: https://www.drupal.org/project/drupal/issues/2915954
    $form['translations-button'] = [
      '#type' => 'button',
      '#value' => $this->t('Translate this page'),
      '#attributes' => ['class' => ['origins-translation-button', 'hidden']],
      '#attached' => ['library' => ['origins_translations/origins_translations.link_ui']],
      '#ajax' => [
        'callback' => '::displayLanguageOptions',
        'wrapper' => $wrapper_id,
      ],
      '#suffix' => '<div id="' . $wrapper_id . '"></div>',
    ];

    return $form;
  }

  /**
   * AJAX callback to display a select list of languages.
   */
  public function displayLanguageOptions($form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $languages = $this->utilities->getActiveLanguages();
    $code = substr($request->headers->get('accept-language'), 0, 2);

    // Allow for Simplified (zh-cn) and Traditional (zh-tw) Chinese.
    if ($code === 'zh') {
      $code = strtolower(substr($request->headers->get('accept-language'), 0, 5));
    }

    $url = $this->config('origins_translations.settings')->get('domain');

    // If the domain is set we need to append the current path.
    if (!empty($url)) {
      $url .= $request->getPathInfo();
    }
    else {
      $url = $request->getUri();
    }

    // Provide a translation for 'Select a language' if available for the
    // detected browser language.
    if (array_key_exists($code, $languages) && strpos($code, 'en') !== 0) {
      $translations[''] = $languages[$code][3];
    }
    else {
      $translations[''] = 'Select a language';
    }

    foreach ($languages as $code => $language) {
      $translations[$code . '&u=' . $url] = $language[0];
    }

    $form['translation-select'] = [
      '#type' => 'select',
      '#options' => $translations,
      '#attributes' => ['class' => ['origins-translation-select']],
    ];
    return $form['translation-select'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Method stub to comply with interface.
  }

}
