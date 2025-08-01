<?php

declare(strict_types=1);

namespace Drupal\origins_datadome\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure DataDome settings form.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_datadome_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_datadome.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['ddjskey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JS key'),
      '#default_value' => $this->config('origins_datadome.settings')->get('ddjskey'),
      '#required' => TRUE,
    ];

    $form['ddoptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration options'),
      '#description' => $this->t('@link', ['@link' => Link::fromTextAndUrl($this->t('DataDome configuration reference'), Url::fromUri('https://docs.datadome.co/docs/javascript-tag#configuration', ['attributes' => ['target' => '_blank']]))->toString()]),
      '#default_value' => $this->config('origins_datadome.settings')->get('ddoptions'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if (preg_match('/[^a-zA-Z\d]/', $values['ddjskey']) == 1) {
      $form_state->setErrorByName('ddjskey', $this->t('Key must contain alphanumeric characters only.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if (!empty($values['ddoptions'])) {
      if (!str_starts_with($values['ddoptions'], '{')) {
        $values['ddoptions'] = '{' . PHP_EOL . $values['ddoptions'];
      }
      if (!str_ends_with($values['ddoptions'], '}')) {
        $values['ddoptions'] = $values['ddoptions'] . PHP_EOL . '}';
      }
    }

    $config = $this->config('origins_datadome.settings');
    $config->set('ddjskey', $values['ddjskey']);
    $config->set('ddoptions', $values['ddoptions']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
