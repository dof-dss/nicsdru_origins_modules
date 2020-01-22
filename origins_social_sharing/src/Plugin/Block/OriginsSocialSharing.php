<?php

namespace Drupal\origins_social_sharing\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'OriginsSocialSharing' block.
 *
 * @Block(
 *  id = "origins_social_sharing",
 *  admin_label = @Translation("Origins social sharing"),
 * )
 */
class OriginsSocialSharing extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'origins_social_sharing';
    return $build;
  }

}
