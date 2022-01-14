<?php

namespace Drupal\origins_layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides customisations to the core Layout Builder.
 *
 * @package Drupal\origins_layouts
 */
class OriginsLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'title' => '',
      'extra_classes' => '',
      'reverse_layout' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $extra_classes = explode(' ', $build['#settings']['extra_classes']);

    // Add default core class.
    $extra_classes[] = 'layout';

    // Add the template ID as a class name.
    $template_id = $build['#layout']->getTemplate();
    $extra_classes[] = $template_id;

    // Add 'reverse' class if requested and check for existing occurrences.
    if ($build['#settings']['reverse_layout']) {
      if (!in_array('reverse', $extra_classes)) {
        $extra_classes[] = 'reverse';
      }
    }
    $build['#settings']['extra_classes'] = ltrim(implode(' ', $extra_classes));

    // Build the title if set.
    if (array_key_exists('title', $build['#settings']) && !empty($build['#settings']['title']['value'])) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => $build['#settings']['title']['element'],
        '#value' => $build['#settings']['title']['value'],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['extra_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes for layout'),
      '#description' => $this->t('Valid CSS class names separated by spaces.'),
      '#default_value' => $configuration['extra_classes'],
    ];

    $form['reverse_layout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse the layout'),
      '#default_value' => $configuration['reverse_layout'],
    ];

    $form['title'] = [
      '#type' => 'details',
      '#title' => $this->t('Title settings'),
    ];

    $form['title']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Displays a title above the layout region.'),
      '#default_value' => $configuration['title']['value'],
    ];

    $form['title']['element'] = [
      '#type' => 'select',
      '#title' => $this->t('HTML element for title'),
      '#options' => [
        'h1' => 'Heading 1',
        'h2' => 'Heading 2',
        'h3' => 'Heading 3',
        'h4' => 'Heading 4',
      ],
      '#default_value' => $configuration['title']['element'],
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
      // Form state messages are currently broken and do not display
      // to the end user.
      // It will however prevent the form from being submitted.
      $form_state->setErrorByName('extra_classes', $this->t('Invalid CSS name'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['extra_classes'] = $form_state->getValue('extra_classes');
    $this->configuration['reverse_layout'] = $form_state->getValue('reverse_layout');
  }

}
