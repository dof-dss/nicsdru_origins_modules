/**
 * @file
 * Attaches behaviors for the Origins Translations module.
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.origins_translate = {
    attach: function (context, settings) {

      $('.origins-translation-link', context).once('origins-translation').each(function () {
        if (navigator.language.substr(0,2) !== 'en') {
          $(this).load('/origins-translations/translation-link-ui/title/' + navigator.language.substr(0,2));
        }
      });

      $('select.origins-translation', context).once('origins-translation').each(function () {
        $(this).change(function () {
          window.open(($(this).val()));
        });
      });
    }
  };


})(jQuery, Drupal, drupalSettings);
