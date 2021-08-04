/**
 * @file
 * Attaches behaviors for the Origins Translations module.
 */

(function($, Drupal) {
  'use strict';

  // If ajax is enabled, we want to hide items that are marked as hidden in
  // our example.
  if (Drupal.ajax) {
    $('.ajax-example-hide').hide();
  }

  function disableLinkUi(i, elm) {
    $(elm).addClass('hidden');
  }

  function enableButtonUi(i, elm) {
    $(elm).removeClass('hidden');
    if (navigator.language.substr(0,2) !== 'en') {
      $(elm).load('/origins-translations/translation-link-ui/title/' + navigator.language.substr(0,2));
    }
  }

  Drupal.behaviors.originsTranslate = {
    attach: function (context, settings) {

      $('.origins-translation-link', context).once('origins-translation').each(disableLinkUi);
      $('.origins-translation-button', context).once('origins-translation').each(enableButtonUi);

      $('.origins-translation-select', context).once('origins-translation').each(function () {
        $(this).change(function () {
          if ($(this).val().length > 0) {
            window.open(('https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' + $(this).val()));
          }
        });
      });
    }
  };


})(jQuery, Drupal, drupalSettings);
