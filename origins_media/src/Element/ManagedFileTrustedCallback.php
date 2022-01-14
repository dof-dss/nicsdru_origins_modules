<?php

namespace Drupal\origins_media\Element;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Trusted callbacks for Managed File form element.
 *
 * Provides trusted callbacks for the Managed File form element in Media
 * entity editing forms.
 *
 * @see origins_media_form_alter()
 */
class ManagedFileTrustedCallback implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processManagedFile'];
  }

  /**
   * Process callback for core's ManagedFile form element.
   */
  public static function processManagedFile(&$element) {
    if (!empty($element['remove_button'])) {
      $element['remove_button']['#access'] = FALSE;
    }
    return $element;
  }

}
