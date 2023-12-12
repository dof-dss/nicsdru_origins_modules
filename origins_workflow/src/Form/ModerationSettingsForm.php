<?php

declare(strict_types = 1);

namespace Drupal\origins_workflow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Configure Origins: Moderation settings for this site.
 */
final class ModerationSettingsForm extends ConfigFormBase {

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
    return ['origins_workflow.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $view = Views::getView('workflow_moderation');
    $displays = $view->storage->get('display');
    unset($displays['default']);

    $types = [];
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $types[$node_type->id()] = $node_type->label();
    }

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
        '#title' => $this->t(''),
      ];

      $form[$display]['node_type_filter']['node_type'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t("Display Content types for the '@title' View.", ['@title' => $data['display_title']]),
        '#description' => $this->t('Unselect all to display all content types.'),
        '#options' => $types,
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('origins_workflow.settings')
      ->set('example', $form_state->getValue('example'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
