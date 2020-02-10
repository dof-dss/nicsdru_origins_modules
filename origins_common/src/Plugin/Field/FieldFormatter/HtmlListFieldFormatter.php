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
      'list_classes' => '',
      'display_item_link' => FALSE,
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

    // TODO: There is no form validator call for plugin settings forms,
    // possibly look at using an ajax callback to validate CSS classes.
    $elements['list_classes'] = [
      '#title' => $this->t('List classes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('list_classes'),
      '#description' => $this->t('Space separated list of classes for the root list element.'),
    ];

    $elements['display_item_link'] = [
      '#title' => $this->t('Display link to entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_item_link'),
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
    $settings = $this->getSettings();

    $element['#theme'] = 'item_list';
    $element['#list_type'] = $settings['list_type'];
    $element['#attributes']['class'] = $settings['list_classes'];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($settings['display_item_link']) {
        $list_items[] = $entity->toLink();
      }
      else {
        $list_items[] = $entity->label();
      }
    }

    $element['#items'] = $list_items;

    return $element;
  }

}
