<?php

declare(strict_types = 1);

namespace Drupal\origins_workflow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Configure Origins: Moderation settings for this site.
 */
final class ModerationSettingsForm extends ConfigFormBase {

  const SETTINGS = 'origins_workflow.moderation.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_workflow_moderation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [ModerationSettingsForm::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $moderated_content_view = Views::getView('moderated_content');

    if ($moderated_content_view->storage->status()) {
      \Drupal::messenger()->addWarning(
        $this->t("Core 'Moderated content' View is enabled. We recommend disabling this on the @link and exporting site configuration.", [
          '@link' => Link::createFromRoute('Views admin page', 'entity.view.collection')->toString()
        ])
      );
    }

    $view = Views::getView('workflow_moderation');
    $displays = $view->storage->get('display');
    unset($displays['default']);

    $types = [];
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $types[$node_type->id()] = $node_type->label();
    }

    $view_overrides = $this->config('origins_workflow.moderation.settings')->get('view_overrides');

    $form['views'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Moderation Displays'),
    ];

    foreach ($displays as $display => $data) {
      $form[$display] = [
        '#type' => 'details',
        '#title' => $data['display_title'],
        '#group' => 'views',
      ];

      $form[$display]['node_type_filter'] = [
        '#type' => 'fieldset',
      ];

      $form[$display][$display . '_disable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable this display.'),
        '#default_value' => $view_overrides[$display]['disable'] ?? FALSE,
        '#weight' => -10,
      ];

      $form[$display]['node_type_filter'][$display . '_node_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t("Display Content types for the '@title' View.", ['@title' => $data['display_title']]),
        '#description' => $this->t('Unselect all to display all content types.'),
        '#default_value' => array_filter($view_overrides[$display]['filtered_node_types'] ?? [], 'is_string'),
        '#options' => $types,
      ];

    }

    $form['view_displays'] = [
      '#type' => 'hidden',
      '#value' => implode(',', array_keys($displays))
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $displays = explode(',', $form_state->getValue('view_displays'));
    $settings = [];

    foreach ($displays as $display) {
      $settings[$display]['filtered_node_types'] = $form_state->getValue($display . '_node_types');
      $settings[$display]['disable'] = $form_state->getValue($display . '_disable');
    }

    $this->config(ModerationSettingsForm::SETTINGS)
      ->set('view_overrides', $settings)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
