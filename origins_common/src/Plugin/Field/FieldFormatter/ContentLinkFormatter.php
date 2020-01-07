<?php

namespace Drupal\origins_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Field formatter for displaying a link to the entity.
 *
 * @FieldFormatter(
 *   id = "content_link",
 *   label = @Translation("Content link"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class ContentLinkFormatter extends FormatterBase {

  public static function defaultSettings()
  {
    return [
        'link_text' => 'more',
        'trim_text' => FALSE,
        'trim_length' => '600',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['link_text'] = [
      '#title' => t('Link text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_text'),
      '#required' => TRUE,
    ];

    $element['trim_text'] = [
      '#title' => t('Trim the text'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('trim_text'),
    ];

    $element['trim_length'] = [
      '#title' => t('Trimmed limit'),
      '#type' => 'number',
      '#field_suffix' => t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#description' => t('If the summary is not set, the trimmed %label field will end at the last full sentence before this character limit.', ['%label' => $this->fieldDefinition->getLabel()]),
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][trim_text]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('trim_text')) {
      $summary[] = t('Link text: @link_text, trimmed to @trim_length characters', ['@link_text' => $this->getSetting('link_text'), '@trim_length' => $this->getSetting('trim_length')]);
    } else {
      $summary[] = t('Link text: @link_text', ['@link_text' => $this->getSetting('link_text')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      ];
    }

    $link = Link::fromTextAndUrl('more', $items->getEntity()->toUrl());
    $elements[] = $link->toRenderable();

    return $elements;
  }

}
