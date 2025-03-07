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
    $form['form_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable form descriptions'),
      '#description' => $this->t('Moves the description on content form fields underneath the title.'),
      '#default_value' => $this->config('origins_forms.settings')->get('enable_form_descriptions'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('origins_forms.settings')
      ->set('enable_form_descriptions', $form_state->getValue('form_descriptions'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
