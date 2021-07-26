<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Utility\UrlHelper;
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

    $form['page'] = [
      '#type' => 'details',
      '#title' => $this->t('Translations page'),
      '#open' => TRUE,
    ];

    $form['page']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $this->config('origins_translations.settings')->get('title'),
    ];

    $form['page']['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#required' => TRUE,
      '#format' => $this->config('origins_translations.settings')->get('content')['format'],
      '#default_value' => $this->config('origins_translations.settings')->get('content')['value'],
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
    ];

    $form['options']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace domain'),
      '#description' => $this->t("Typically you would enter the live site domain if using this on a development site which Google Translate won't have access to."),
      '#default_value' => $this->config('origins_translations.settings')->get('domain'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $domain = trim($form_state->getValue('domain'));

    if (!empty($domain) && UrlHelper::isValid($domain) === FALSE) {
      $form_state->setErrorByName('domain', $this->t('Domain must be a valid URL.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('origins_translations.settings')
      ->set('title', $form_state->getValue('title'))
      ->set('content', $form_state->getValue('content'))
      ->set('domain', trim($form_state->getValue('domain'),' /'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
