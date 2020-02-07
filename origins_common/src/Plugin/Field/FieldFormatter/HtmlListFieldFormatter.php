<?php

namespace Drupal\origins_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field formatter "HtmlListFieldFormatter".
 *
 * @FieldFormatter(
 *   id = "html_list_formatter",
 *   label = @Translation("HTML List"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class HtmlListFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'list_type' => 'ul',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['list_type'] = [
      '#title' => $this->t('List type'),
      '#type' => 'select',
      '#options' => [
        'ul' => $this->t('Unordered list'),
        'ol' => $this->t('Ordered list'),
      ],
      '#default_value' => $this->getSetting('list_type'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays HTML lists');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = ['#markup' => $item->value];
    }

    return $element;
  }
}
