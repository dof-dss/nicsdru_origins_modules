<?php

namespace Drupal\origins_translations\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a link to translations for the current URL.
 *
 * @Block(
 *   id = "origins_translations_block",
 *   admin_label = @Translation("Origins Translation"),
 *   category = @Translation("Origins")
 * )
 */
class OriginsTranslationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('Translate this page'),
    ];
    return $build;
  }

}
