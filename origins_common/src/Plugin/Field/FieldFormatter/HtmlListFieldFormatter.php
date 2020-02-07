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
    $summary[] = $this->t('List type: @list_type', ['@list_type' => $this->getSetting('list_type')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $list_items = [];

    $list_type = $this->getSetting('list_type');
    $element['#theme'] = 'item_list';
    $element['#list_type'] = $list_type;

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $list_items[] = $entity->label();
    }

    $element['#items'] = $list_items;

    return $element;
  }
}
