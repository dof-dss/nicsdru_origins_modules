<?php

namespace Drupal\nicsdru_layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class NicsDruLayouts.
 *
 * @package Drupal\nicsdru_layouts
 */
class NicsDruLayout extends LayoutDefault implements PluginFormInterface
{

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'extra_classes' => '',
      'reverse_layout' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['extra_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes'),
      '#description' => $this->t('Valid CSS class names separated by spaces.'),
      '#default_value' => $configuration['extra_classes'],
    ];

    $form['reverse_layout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse the layout'),
      '#default_value' => $configuration['reverse_layout'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Regex for valid CSS names: Cannot start with a digit, two hyphens or
    // a hyphen followed by a number.
    $regex = '/(^|\s)(\d|--|-\d)/m';
    preg_match_all($regex, $values['extra_classes'], $matches, PREG_SET_ORDER, 0);
    if (count($matches) > 0) {
      // Form state messages are currently broken and do not display to the end user.
      // It will however prevent the form from being submitted.
      $form_state->setErrorByName('extra_classes', $this->t('Invalid CSS name'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['extra_classes'] = $form_state->getValue('extra_classes');
    $this->configuration['reverse_layout'] = $form_state->getValue('reverse_layout');
  }

}
