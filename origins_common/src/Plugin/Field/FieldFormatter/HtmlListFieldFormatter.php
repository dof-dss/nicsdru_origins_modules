<?php

namespace Drupal\origins_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

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
