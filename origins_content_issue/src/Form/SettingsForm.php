<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Origins content issue settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  private $modulePath;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected TypedConfigManagerInterface $typedConfigManager,
  ) {
    $this->setConfigFactory($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_content_issue_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['origins_content_issue.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $this->modulePath = \Drupal::service('module_handler')->getModule('origins_content_issue')->getPath();

    $selected_icon = $this->config('origins_content_issue.settings')->get('report_icon') ?? 'icon_report_1.png';

    for ($i=1; $i<=6; $i++) {
      $icon_options['icon_report_' . $i . '.png'] = 'Style ' . $i;
    }

    $icon_options['custom'] = $this->t('Custom');

    $form['report_icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Report icon'),
      '#description' => $this->t('Select either a predefined or custom icon that will be shown to users for submitting content issue reports.'),
      '#default_value' => $this->config('origins_content_issue.settings')->get('report_icon'),
      '#options' => $icon_options,
      '#ajax' => [
        'callback' => '::reportIcon',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'icon-preview',
        'progress' => [
          'type' => 'none',
          'message' => $this->t('Previewing icon'),
        ],
      ]
    ];

    $form['custom_icon'] = [
      '#type' => 'file',
      '#description' => $this->t('Allowed file types: gif, png, jpg, jpeg'),
      '#states' => [
        'visible' => [
          ':input[name="report_icon"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $markup = [
      '#theme' => 'image',
      '#width' => 40,
      '#height' => 20,
      '#uri' => $this->modulePath . '/assets/' . $selected_icon,
      '#prefix' => '<div id="icon-preview">',
      '#suffix' => '</div>',
    ];

    if ($selected_icon == 'custom') {
      $custom_icon_url = $this->config('origins_content_issue.settings')->get('custom_icon_url') ?? '';
      $markup['#uri'] = $custom_icon_url;
    }

    $markup = \Drupal::service('renderer')->render($markup);

    $form['report_icon_preview'] = [
      '#type' => 'item',
      '#markup' => $markup,
      '#title' => $this->t('Icon preview'),
    ];

    if ($selected_icon = $form_state->getValue('report_icon')) {
      $form['report_icon_preview']['#markup'] = '<div id="icon-preview">image ' . $selected_icon . '</div>';
    }

    return parent::buildForm($form, $form_state);
  }

  public function reportIcon(array &$form, FormStateInterface $form_state) {
    $selected_icon = $form_state->getValue('report_icon');

    $markup = [
      '#theme' => 'image',
      '#uri' => $this->modulePath . '/assets/' . $selected_icon,
      '#width' => 40,
      '#height' => 20,
      '#prefix' => '<div id="icon-preview">',
      '#suffix' => '</div>',
    ];

    if ($selected_icon === 'custom') {
      $custom_icon_url = $this->config('origins_content_issue.settings')->get('custom_icon_url') ?? '';

      if (empty($custom_icon_url)) {
        $markup = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('No custom icon saved.'),
          '#prefix' => '<div id="icon-preview">',
          '#suffix' => '</div>',
        ];
      }
    }

    return [$markup];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $custom_icon = $form_state->getValue('custom_icon');

    if (!empty($custom_icon) && $form_state->getValue('report_icon') == 'custom') {
      if (!in_array($custom_icon['' . "\0" . 'Symfony\\Component\\HttpFoundation\\File\\UploadedFile' . "\0" . 'mimeType'], ['image/png', 'image/gif', 'image/jpeg'])) {
        $form_state->setErrorByName('custom_icon', $this->t('Custom icon file is not a valid format.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $report_icon = $form_state->getValue('report_icon');

    if ($report_icon == 'custom') {
      $validators = ['FileExtension' => ['gif png jpg jpeg']];
      $custom_icon_directory = 'public://origins_content_issue/';

      \Drupal::service('file_system')->prepareDirectory($custom_icon_directory, FileSystemInterface::CREATE_DIRECTORY);

      // Handle the upload manually.
      if ($file = file_save_upload('custom_icon', $validators, $custom_icon_directory, 0, FileExists::Replace)) {
        $file->setPermanent();
        $file->save();

        $this->config('origins_content_issue.settings')
          ->set('custom_icon_url', $file->createFileUrl())
          ->save();
      }
    }

    $this->config('origins_content_issue.settings')
      ->set('report_icon', $form_state->getValue('report_icon'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
