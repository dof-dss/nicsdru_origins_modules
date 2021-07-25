<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Origins Translations settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $this->config('origins_translations.settings')->get('title'),
    ];

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#required' => TRUE,
      '#format' => $this->config('origins_translations.settings')->get('content')['format'],
      '#default_value' => $this->config('origins_translations.settings')->get('content')['value'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('origins_translations.settings')
      ->set('title', $form_state->getValue('title'))
      ->set('content', $form_state->getValue('content'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
