<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\origins_cloud_tasks\CloudTasksManager;

/**
 * Configure Origins cloud tasks settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_cloud_tasks_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_cloud_tasks.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $timezone_offset = $this->config('origins_cloud_tasks.settings')->get('timezone_offset') ?? '0';
    $offset_value = $this->config('origins_cloud_tasks.settings')->get('callback_offset') ?? '5';

    $form['auth'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication'),
      '#open' => FALSE,
    ];

    $form['auth']['adc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ADC JSON'),
      '#description' => $this->t('See the Google Cloud documentation %link',
        [
          '%link' => Link::fromTextAndUrl('Set up Application Default Credentials', Url::fromUri('https://cloud.google.com/docs/authentication/provide-credentials-adc'))->toString()
        ]
      ),
    ];

    $adc_file_exists = file_exists(CloudTasksManager::adcPath());

    $adc_info = $this->t('An ADC file @status present in the filesystem. @additional', [
      '@status' => $adc_file_exists ? 'is' : 'is not',
      '@additional' => $adc_file_exists ? ' For security purposes the file contents are not shown in the form .' : ' To use this service, valid ADC JSON must be pasted into form field.',
    ]);

    $form['auth']['adc_info'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $adc_info,
      '#attributes' => ['style' => $adc_file_exists ? 'color: green;' : 'color: red;'],
    ];

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#description' => $this->t('Google Cloud project ID. (available in the GC dashboard)'),
      '#default_value' => $this->config('origins_cloud_tasks.settings')->get('project_id'),
      '#size' => 20,
      '#required' => TRUE,
    ];

    $form['queue_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Queue ID'),
      '#description' => $this->t('Google Cloud Tasks queue ID. (available in the tasks dashboard)'),
      '#default_value' => $this->config('origins_cloud_tasks.settings')->get('queue_id'),
      '#required' => TRUE,
    ];

    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Google Cloud region '),
      '#default_value' => $this->config('origins_cloud_tasks.settings')->get('region') ?? 'europe-west2',
      '#options' => [
        'europe-west2' => 'United Kingdom',
        'europe-west4' => 'Netherlands',
        'europe-west1' => 'Belgium',
        'europe-west9' => 'France',
        'europe-west3' => 'Germany',
      ],
      '#required' => TRUE,
    ];

    $form['timezone_offset'] = [
      '#type' => 'range',
      '#title' => $this->t('Timezone offset'),
      '#description' => $this->t('@offset_value hours.', ['@offset_value' => $timezone_offset]),
      '#min' => -2,
      '#max' => 2,
      '#step' => 1,
      '#default_value' => $timezone_offset,
      '#attributes' => [
        'oninput' => 'document.getElementById("edit-timezone-offset--description").innerText = this.value + " hours."'
      ],
    ];

    $form['callback_offset'] = [
      '#type' => 'range',
      '#title' => $this->t('Callback offset (delay added to scheduled time)'),
      '#description' => $this->t('@offset_value seconds.', ['@offset_value' => $offset_value]),
      '#min' => 5,
      '#max' => 180,
      '#step' => 1,
      '#default_value' => $offset_value,
      '#attributes' => [
        'oninput' => 'document.getElementById("edit-callback-offset--description").innerText = this.value + " seconds."'
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $adc_data = $form_state->getValue('adc');

    if (!empty($adc_data)) {
      $json_data = json_decode((string) $adc_data, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('adc', 'Invalid JSON: ' . json_last_error_msg());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $fs = \Drupal::service('file_system');

    if (!empty($values['adc'])) {
      $destination = 'private://google_application_credentials.json';
      $fs->saveData($values['adc'], $destination, FileExists::Replace);
    }

    $config = $this->config('origins_cloud_tasks.settings');
    $config->set('project_id', $values['project_id']);
    $config->set('queue_id', $values['queue_id']);
    $config->set('region', $values['region']);
    $config->set('timezone_offset', $values['timezone_offset']);
    $config->set('callback_offset', $values['callback_offset']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
