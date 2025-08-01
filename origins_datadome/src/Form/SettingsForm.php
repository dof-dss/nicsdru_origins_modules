<?php

declare(strict_types=1);

namespace Drupal\origins_datadome\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
      '#title' => $this->t('DataDome JS key'),
      '#default_value' => $this->config('origins_datadome.settings')->get('ddjskey'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate key for spaces etc.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('origins_datadome.settings')
      ->set('ddjskey', $form_state->getValue('ddjskey'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
