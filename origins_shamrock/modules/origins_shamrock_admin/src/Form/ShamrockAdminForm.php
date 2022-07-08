<?php

namespace Drupal\origins_shamrock_admin\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Operation Shamrock admin settings form.
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
      '#maxlength' => 255,
      '#default_value' => $config->get('body'),
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['shamrock']['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner link URL'),
      '#description' => $this->t('Enter the url the banner should link to.'),
      '#maxlength' => 255,
      '#default_value' => $config->get('link_url'),
      '#size' => 64,
      '#weight' => '3',
    ];
    $form['shamrock']['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner link text'),
      '#description' => $this->t('Enter the link text for the banner link.'),
      '#maxlength' => 255,
      '#default_value' => $config->get('link_text'),
      '#size' => 64,
      '#weight' => '3',
    ];
    $form['shamrock']['styles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS styles'),
      '#default_value' => $config->get('styles'),
      '#weight' => '4',
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Publish the banner.'),
      '#default_value' => $config->get('published'),
      '#weight' => '5',
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
      ->set('link_url', $form_state->getValue('link_url'))
      ->set('link_text', $form_state->getValue('link_text'))
      ->set('styles', $form_state->getValue('styles'))
      ->set('published', (bool) $form_state->getValue('published'))
      ->set('modified', time())
      ->save();

    Cache::invalidateTags(['origins:operation_shamrock']);

    parent::submitForm($form, $form_state);
  }

}
