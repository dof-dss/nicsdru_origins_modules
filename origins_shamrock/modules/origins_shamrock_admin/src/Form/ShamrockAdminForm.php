<?php

namespace Drupal\origins_shamrock_admin\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShamrockAdminForm.
 */
class ShamrockAdminForm extends ConfigFormBase {
  const SETTINGS = 'origins_shamrock.admin.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shamrock_admin_form';
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
      '#markup' => 'Operation Shamrock is the NI arrangements for a cabinet office lead directive known as London Bridge. Should this be triggered, this page provides a central editorial console to insert and amend the text for a banner which will be rendered on sites which have enabled the Operation Shamrock module.',
    ];

    $form['shamrock'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Operation Shamrock banner details'),
    ];

    $form['shamrock']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The title of banner'),
      '#description' => $this->t('Generates the title of the banner.'),
      '#maxlength' => 64,
      '#default_value' => $config->get('title'),
      '#size' => 60,
      '#weight' => '1',
    ];
    $form['shamrock']['body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The body text of the banner'),
      '#description' => $this->t('Generates the body text for the banner.'),
      '#maxlength' => 64,
      '#default_value' => $config->get('body'),
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['shamrock']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner URL'),
      '#description' => $this->t('Enter the url which the banner should point to.'),
      '#maxlength' => 64,
      '#default_value' => $config->get('url'),
      '#size' => 64,
      '#weight' => '3',
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Publish the banner.'),
      '#default_value' => $config->get('published'),
      '#weight' => '4',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '50',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('title', $form_state->getValue('title'))
      ->set('body', $form_state->getValue('body'))
      ->set('url', $form_state->getValue('url'))
      ->set('published', (bool) $form_state->getValue('published'))
      ->set('modified', time())
      ->save();

    Cache::invalidateTags(['origins:operation_shamrock']);

    parent::submitForm($form, $form_state);
  }

}
