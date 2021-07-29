<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\NullLockBackend;
use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;


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

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $this->config('origins_translations.settings')->get('title'),
    ];

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#required' => TRUE,
      '#format' => $this->config('origins_translations.settings')->get('content')['format'],
      '#default_value' => $this->config('origins_translations.settings')->get('content')['value'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('origins_translations.settings')
      ->set('title', $form_state->getValue('title'))
      ->set('content', $form_state->getValue('content'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
