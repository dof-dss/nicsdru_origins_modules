<?php

namespace Drupal\origins_translations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Origins Translations page settings for this site.
 */
class TranslationsPageForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_translation_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings.translation_page'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['override_default_route'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a URL for the translation page'),
      '#default_value' => $this->config('origins_translations.settings')->get('override_default_route'),
    ];

    $form['override_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url to translation page'),
      '#default_value' => $this->config('origins_translations.settings')->get('override_url'),
      '#states' => [
        'visible' => [
          ':input[name="override_default_route"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'hr',
    ];

    $form['page_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="override_default_route"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['page_container']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->config('origins_translations.settings')->get('title'),
    ];

    $form['page_container']['summary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary'),
      '#default_value' => $this->config('origins_translations.settings')->get('summary')['value'],
    ];

    $form['page_container']['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#format' => $this->config('origins_translations.settings')->get('content')['format'],
      '#description' => $this->t('Token: [origins:translations_languages_list] - Displays a list of active site languages linking to Google Translate.'),
      '#default_value' => $this->config('origins_translations.settings')->get('content')['value'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation for required fields depending on the selected form state.
    if ($form_state->getValue('override_default_route')) {
      if (empty($form_state->getValue('override_url'))) {
        $form_state->setErrorByName('override_url', 'You must provide a URL.');
      }
    } else {
      if (empty($form_state->getValue('title'))) {
        $form_state->setErrorByName('title', 'You must provide a title.');
      }

      if (empty($form_state->getValue('content')['value'])) {
        $form_state->setErrorByName('content', 'You must provide some content.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('origins_translations.settings')
      ->set('override_default_route', $form_state->getValue('override_default_route'))
      ->set('override_url', $form_state->getValue('override_url'))
      ->set('title', $form_state->getValue('title'))
      ->set('summary', $form_state->getValue('summary'))
      ->set('content', $form_state->getValue('content'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
