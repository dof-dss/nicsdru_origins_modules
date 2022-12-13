<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit an Origins Translations language.
 */
class LanguageEditForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_languages.edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings.languages'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $lang_code = $this->getRouteMatch()->getParameter('code');

    $languages = $this->config('origins_translations.languages')->getRawData();

    $language = $languages[$lang_code];

    // $language[0] = language name (e.g. "French").
    $lang_name = $language[0];

    // $language[2] = translation of "Translate this page" (e.g. "Traduire cette page").
    $lang_translate_this_page = $language[2];

    // $language[3] = translation of "Select a language" (e.g. "Sélectionnez une langue").
    $lang_select = $language[3];

    // $language[4] = native language name (e.g. "Français").
    $lang_native_name = $language[4] ?? '';

    // $language[5] = language text direction (e.g. "rtl").
    // This is used to set the dir attribute on HTML elements containing translated text.
    $lang_direction = $language[5] ?? 'ltr';

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t("Translations for @language", ['@language' => $lang_name]),
    ];

    $form['title_native'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Native translation of "@language"', ['@language' => $lang_name]),
      '#description' => $this->t('Translate via <a href="@google-translate">Google translate</a>', ['@google-translate' => 'https://translate.google.co.uk']),
      '#default_value' => $lang_native_name,
    ];

    $form['translation_this_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Translate this page"'),
      '#description' => $this->t('Translate via <a href="@google-translate">Google translate</a>', ['@google-translate' => 'https://translate.google.co.uk']),
      '#default_value' => $lang_translate_this_page,
    ];

    $form['translation_select'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Select a language"'),
      '#description' => $this->t('Translate via <a href="@google-translate">Google translate</a>', ['@google-translate' => 'https://translate.google.co.uk']),
      '#default_value' => $lang_select,
    ];

    $form['text_direction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Text direction"'),
      '#description' => $this->t('Used to set "dir" language direction attribute on HTML elements containing translated text. For most languages this should be "ltr" meaning the language is read from left to right (and top to bottom).'),
      '#default_value' => $lang_direction,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $lang_code = $this->getRouteMatch()->getParameter('code');
    $languages = $this->config('origins_translations.languages')->getRawData();

    $languages[$lang_code][2] = trim($values['translation_this_page']);
    $languages[$lang_code][3] = trim($values['translation_select']);
    $languages[$lang_code][4] = trim($values['title_native']);
    $languages[$lang_code][5] = trim($values['text_direction']);

    $this->configFactory()->getEditable('origins_translations.languages')->setData($languages)->save();
    parent::submitForm($form, $form_state);
  }

}
