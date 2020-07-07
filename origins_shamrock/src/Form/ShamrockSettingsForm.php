<?php

namespace Drupal\origins_shamrock\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShamrockSettingsForm.
 */
class ShamrockSettingsForm extends ConfigFormBase {
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
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['introduction'] = [
      '#markup' => 'Operation Shamrock is the NI arrangements for a cabinet office lead directive known as London Bridge. Should this be triggered, this admin page gives a site editor the option as to whether to render the banner on the front page of the site. It is turned by default for some domains',
    ];

    $form['show_the_banner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the banner'),
      '#description' => $this->t('Display the Operation Shamrock banner on the website.'),
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
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('show_banner', (bool) $form_state->getValue('show_the_banner'))
      ->save();

    Cache::invalidateTags(['origins:operation_shamrock']);
    parent::submitForm($form, $form_state);
  }

}
