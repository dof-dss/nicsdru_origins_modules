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

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t("Translations for @language", ['@language' => $language[0]]),
    ];

    $form['translation_this_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Translate this page"'),
      '#description' => $this->t('Translate via <a href="@google-translate">Google translate</a>', ['@google-translate' => 'https://translate.google.co.uk']),
      '#default_value' => $language[2],
    ];

    $form['translation_select'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Select a language"'),
      '#description' => $this->t('Translate via <a href="@google-translate">Google translate</a>', ['@google-translate' => 'https://translate.google.co.uk']),
      '#default_value' => $language[3],
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

    $this->configFactory()->getEditable('origins_translations.languages')->setData($languages)->save();
    parent::submitForm($form, $form_state);
  }

}
