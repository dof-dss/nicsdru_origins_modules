<?php

namespace Drupal\origins_shamrock\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Operation Shamrock settings form.
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
      '#markup' => 'Operation Shamrock is the NI arrangements for a cabinet office lead directive known as London Bridge. Should this be triggered, this admin page gives a site editor the option as to whether to render the banner on the front page of the site. It is turned off by default for some domains',
    ];

    $form['show_the_banner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the banner'),
      '#description' => $this->t('Display the Operation Shamrock banner on the website.'),
      '#default_value' => $config->get('show_banner'),
      '#weight' => '0',
    ];

    $form['service_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service domain'),
      '#description' => $this->t('Domain to query for the Shamrock data.'),
      '#default_value' => $config->get('service_url'),
      '#required' => TRUE,
      '#weight' => '1',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '10',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('show_banner', (bool) $form_state->getValue('show_the_banner'))
      ->set('service_url', $form_state->getValue('service_url'))
      ->save();

    $moduleHandler = \Drupal::service('module_handler');

    // If the Content Security Policy module is enabled, add service url.
    if ($moduleHandler->moduleExists('csp')) {
      $csp_config = $this->configFactory->getEditable('csp.settings');
      $report_only = $csp_config->get('report-only');
      $service_url = $form_state->getValue('service_url');
      $service_domain = substr($service_url, 0, strpos($service_url, '/', 8));

      if (!in_array($service_domain, $report_only['directives']['script-src']['sources'])) {
        $report_only['directives']['script-src']['sources'][] = $service_domain;
        $csp_config->set('report-only', $report_only);
        $csp_config->save();
        $this->messenger()->addMessage($this->t('Added %domain to Content Security Policy', ['%domain' => $service_domain]));
      }
    }

    Cache::invalidateTags(['origins:operation_shamrock']);
    parent::submitForm($form, $form_state);
  }

}
