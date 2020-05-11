<?php

namespace Drupal\origins_shamrock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShamrockSettingsForm.
 */
class ShamrockSettingsForm extends FormBase {
  const SETTINGS = 'origins_shamrock.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shamrock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(SETTINGS);

    $form['show_the_banner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the banner'),
      '#description' => $this->t('Display the Operation Shamrock banner on the website'),
      '#default_value' => $config->get('show_banner'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(SETTINGS)
      ->set('show_banner', $form_state->getValue('show_the_banner'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
