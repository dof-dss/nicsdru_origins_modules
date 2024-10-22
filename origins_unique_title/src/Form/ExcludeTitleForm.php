<?php

namespace Drupal\origins_unique_title\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements admin form to exclude nodes ID from the unique title check.
 */
class ExcludeTitleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'origins_unique_title.excludesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_exclude_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('origins_unique_title.excludesettings');

    $message_exclude_ids = "If there are any specific node's ID that shouldn't be validated. List them on new lines";

    $form['exclude_ids_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Node IDs'),
      '#description' => $this->t($message_exclude_ids),
      '#default_value' => $config->get('exclude_ids_list'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('exclude_ids_list')) {
      $exclude_ids = explode(PHP_EOL, $form_state->getValue('exclude_ids_list'));
      foreach ($exclude_ids as $id) {
        // Make sure url is 'clean'.
        $id = str_replace(["\n", "\t", "\r"], '', $id);
        $pass = FALSE;
        if (is_numeric($id)) {
          $pass = TRUE;
        }
        if (!$pass) {
          $form_state->setErrorByName('exclude_ids_list', $this->t("Node ids must be numeric"));
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('origins_unique_title.excludesettings')
      ->set('exclude_ids_list', $form_state->getValue('exclude_ids_list'))
      ->save();
  }

}
