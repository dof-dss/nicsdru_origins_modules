<?php

declare(strict_types=1);

namespace Drupal\origins_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Origins: Forms settings for this site.
 */
final class FormsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_forms_forms_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_forms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $max_revisions_warning_caution_limit = $this->config('origins_forms.settings')->get('revisions_warning_caution_limit') ?? 50;

    $form['form_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable form descriptions'),
      '#description' => $this->t('Moves the description on content form fields to underneath the title.'),
      '#default_value' => $this->config('origins_forms.settings')->get('enable_form_descriptions'),
    ];

    $form['revisions_warning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revisions warning'),
      '#description' => $this->t('Warn the user or lockdown a node when it has an excessive number of revisions.'),
      '#default_value' => $this->config('origins_forms.settings')->get('enable_revisions_warning'),
    ];

    $form['revisions_warning_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Revisions warning settings'),
      '#states' => [
        'visible' => [
          ':input[name="revisions_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['revisions_warning_settings']['revisions_warning_caution_limit'] = [
      '#type' => 'number',
      '#min' => 10,
      '#max' => $max_revisions_warning_caution_limit,
      '#title' => $this->t('Caution limit'),
      '#description' => $this->t('The number of revisions after which a caution will be displayed to the user.'),
      '#default_value' => $max_revisions_warning_caution_limit,
      '#states' => [
        'required' => [
          ':input[name="revisions_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['revisions_warning_settings']['revisions_warning_lockdown_limit'] = [
      '#type' => 'number',
      '#min' => $max_revisions_warning_caution_limit + 1,
      '#max' => $max_revisions_warning_caution_limit * 5,
      '#title' => $this->t('Lockdown limit'),
      '#description' => $this->t('The number of revisions after which the node save options will be disabled (excluding administrators).'),
      '#default_value' => $this->config('origins_forms.settings')->get('revisions_warning_lockdown_limit'),
      '#states' => [
        'required' => [
          ':input[name="revisions_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('revisions_warning')) {
      if (empty($form_state->getValue('revisions_warning_caution_limit'))) {
        $form_state->setErrorByName('revisions_warning_caution_limit', $this->t('Caution limit is required.'));
      }

      if (empty($form_state->getValue('revisions_warning_lockdown_limit'))) {
        $form_state->setErrorByName('revisions_warning_lockdown_limit', $this->t('Lockdown limit is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('origins_forms.settings')
      ->set('enable_form_descriptions', $form_state->getValue('form_descriptions'))
      ->set('enable_revisions_warning', $form_state->getValue('revisions_warning'))
      ->set('revisions_warning_caution_limit', $form_state->getValue('revisions_warning_caution_limit'))
      ->set('revisions_warning_lockdown_limit', $form_state->getValue('revisions_warning_lockdown_limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
