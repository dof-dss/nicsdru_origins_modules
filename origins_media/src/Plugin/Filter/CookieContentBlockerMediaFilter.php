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

    $text = preg_replace_callback('/(<drupal-media...* data-entity-uuid="(.+)"><\/drupal-media>)/m',
      static function ($matches) {
        $replacement = $matches[1];
        // TODO: Replace with injected service. Use EntityRepository->loadEntityByUuid()
        $entity = array_shift(\Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $matches[2]]));

        $settings = base64_encode('{"button_text":"Show content","show_button":false,"show_placeholder":true,"blocked_message":"<a href=\'https://www.youtube.com/\'>Click here to view the video content</a>","enable_click":true}');

        if ($entity && $entity->bundle() === 'remote_video') {
          $replacement = '<cookiecontentblocker data-settings="' . $settings . '">' . $matches[1] . '</cookiecontentblocker>';
        }
        return $replacement;
      },
      $text
    );

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t("Ensure this filter is placed before 'media embed' and 'cookie content blocker' filters");
  }

}
