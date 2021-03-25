<?php

namespace Drupal\origins_common\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'Cookie Content Blocker Embed Filter' filter.
 *
 * @Filter(
 *   id = "origins_media_cookie_content_blocker_embed_filter",
 *   title = @Translation("Cookie Content Blocker Embed Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class CookieContentBlockerEmbedFilter extends FilterBase {

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

        if ($entity && $entity->bundle() === 'remote_video') {
          $url = $entity->get('field_media_oembed_video')->getString();
          $settings = base64_encode('{"button_text":"Show content","show_button":false,"show_placeholder":true,"blocked_message":"<a href=\''. $url .'\'>Click here to view the video content</a>","enable_click":true}');
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
