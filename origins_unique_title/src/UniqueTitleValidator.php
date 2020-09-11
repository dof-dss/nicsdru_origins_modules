<?php

namespace Drupal\origins_unique_title;

/**
 * Checks whether a content title is unique in a given entity bundle.
 */
class UniqueTitleValidator {

  /**
   * Function to assess whether a node in a given bundle is unique.
   *
   * @param string $title
   *   The title of the content.
   * @param string $bundle
   *   The machine id of the bundle, eg: page.
   * @return bool
   *   Whether or not this is a unique title in this bundle.
   */
  public function isTitleUnique(string $title, string $bundle) {
    return FALSE;
  }

}
