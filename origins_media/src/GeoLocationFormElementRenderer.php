<?php

namespace Drupal\origins_media;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Static callback to alter the GeoLocation form element rendering.
 */
class GeoLocationFormElementRenderer implements RenderCallbackInterface {

  /**
   * Adapts the pre-rendering of the geolocation form element.
   */
  public static function preRender($element) {
    $element['latlng'] = [
      '#type' => 'details',
      '#title' => t('Coordinate details'),
      '#open' => FALSE,
    ];

    // Need to introduce the extra parent element for the fieldset
    // so that the element handler is able to extract the value from our
    // form_state collection. Otherwise, it'll look for 'lat' or 'lng' and find
    // them missing because we've moved them in the form structure.
    array_splice($element['lng']['#array_parents'], -1, 0, ['latlng']);
    array_splice($element['lat']['#array_parents'], -1, 0, ['latlng']);

    // Copy the elements into our fieldset/details container.
    $element['latlng']['lat'] = $element['lat'];
    $element['latlng']['lng'] = $element['lng'];

    // Replace the existing theme_wrapper callback with a generic container one.
    $element['#theme_wrappers'] = ['container'];

    // Get rid of the original elements now we've copied them into our fieldset.
    unset($element['lat'], $element['lng']);

    return $element;
  }

}
