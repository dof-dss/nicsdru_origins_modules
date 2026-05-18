<?php

namespace Drupal\origins_tour\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FeedbackForm extends FormBase {

  public function getFormId() {
    return 'origins_tour_feedback_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your feedback'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send feedback'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $message = $form_state->getValue('message');

    $mailManager = \Drupal::service('plugin.manager.mail');

    $module = 'origins_tour';
    $key = 'feedback_email';

    $to = 'insert-your@email.com';

    $params['subject'] = 'Origins Tour Feedback';

    $params['message'] = $message;

    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    $send = true;

    $mailManager->mail(
      $module,
      $key,
      $to,
      $langcode,
      $params,
      null,
      $send
    );

    $this->messenger()->addMessage(
      $this->t('Thanks for your feedback!')
    );
  }
}
