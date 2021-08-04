<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to select a translation.
 */
class LanguageSelectorForm extends FormBase {

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

    $url = $this->getRequest()->getUri();

    $form['translations-link'] = [
      '#type' => 'link',
      '#title' => $this->t('Translate this page'),
      '#url' => Url::fromRoute('origins_translations.translations-page', ['url' => $url]),
      '#attributes' => ['class' => ['translations-link']],
    ];

    $form['translations-button'] = [
      '#type' => 'button',
      '#value' => $this->t('Translate this page'),
      '#attributes' => ['class' => ['use-ajax', 'hidden']],
      '#ajax' => [
        'callback' => '::displayLanguageOptions',
        'wrapper' => 'translations-select-wrapper',
      ],
      '#suffix' => '<div id="translations-select-wrapper"></div>',
    ];

    return $form;
  }

  public function displayLanguageOptions($form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $config = $this->config('origins_translations.languages');

    $languages = $config->getRawData();
    unset($languages['_core']);

    $languages = array_filter($languages, static fn($language) => $language['1'] === TRUE);

    $url = $request->query->get('url');
    $code = substr($request->headers->get('accept-language'), 0, 2);

    if (array_key_exists($code, $languages) && strpos($code, 'en') !== 0) {
      $translations[''] = $languages[$code][3];
    }
    else {
      $translations[''] = 'Select a language';
    }

    foreach ($languages as $code => $language) {
      $translations['https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' . $code . '&u=' . $url] = $language[0];
    }


    $form['translation-select'] = [
      '#type' => 'select',
      '#options' => $translations,
      '#attributes' => ['class' => ['origins-translation']],
    ];
    return $form['translation-select'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
