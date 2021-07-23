/**
 * @file
 * Attaches behaviors for the Origins Translations module.
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.origins_translate = {
    attach: function (context, settings) {

      // Using once() with more complexity.
      $('select.origins-translation', context).once('mySecondBehavior').each(function () {
        $(this).change(function () {
          window.open(($(this).val()));
        });
      });
    }
  };


})(jQuery, Drupal, drupalSettings);
