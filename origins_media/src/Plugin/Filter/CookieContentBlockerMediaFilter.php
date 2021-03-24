<?php

namespace Drupal\origins_media\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'Cookie Content Blocker Media Filter' filter.
 *
 * @Filter(
 *   id = "origins_media_cookie_content_blocker_media_filter",
 *   title = @Translation("Cookie Content Blocker Media Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class CookieContentBlockerMediaFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    if (stripos($text, '<drupal-media') === FALSE) {
      return new FilterProcessResult($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t("Ensure this filter is placed before 'media embed' and 'cookie content blocker' filters");
  }

}
