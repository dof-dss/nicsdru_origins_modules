<?php

namespace Drupal\origins_shamrock_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShamrockAdminForm.
 */
class ShamrockAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shamrock_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['shamrock_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The title of banner'),
      '#description' => $this->t('Generates the title of the banner.'),
      '#maxlength' => 64,
      '#size' => 60,
      '#weight' => '1',
    ];
    $form['shamrock_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The body text of the banner'),
      '#description' => $this->t('Generates the body text for the banner.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['shamrock_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner URL'),
      '#description' => $this->t('Enter the url which the banner should point to.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '3',
    ];

    $form['shamrock_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Publish the banner.'),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
  }

}
