<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
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
      '#default_value' => $this->config('origins_content_issue.settings')->get('report_icon'),
      '#options' => $icon_options,
      '#ajax' => [
        'callback' => '::reportIcon',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'icon-preview',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Previewing icon'),
        ],
      ]
    ];

    $markup = [
      '#theme' => 'image',
      '#uri' => $this->modulePath . '/assets/' . $selected_icon,
      '#width' => 40,
      '#height' => 20,
      '#prefix' => '<div id="icon-preview">',
      '#suffix' => '</div>',
    ];

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

    if ($selected_icon != 'custom') {

      $selected_icon = [
        '#theme' => 'image',
        '#uri' => $this->modulePath . '/assets/' . $selected_icon,
        '#width' => 40,
        '#height' => 20,
        '#prefix' => '<div id="icon-preview">',
        '#suffix' => '</div>',
      ];

    }

    return [$selected_icon];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('origins_content_issue.settings')
      ->set('report_icon', $form_state->getValue('report_icon'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
