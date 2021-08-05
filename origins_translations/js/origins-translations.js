/**
 * @file
 * Attaches behaviors for the Origins Translations module.
 */

(function($, Drupal) {
  'use strict';

  // Disable the non-javascript link.
  function disableLinkUi(i, elm) {
    $(elm).addClass('hidden');
  }

  // Enable the AJAX button and update title.
  function enableButtonUi(i, elm) {
    $(elm).removeClass('hidden');
    var lang_code = navigator.language.substr(0,2);

    if (lang_code !== 'en') {
      if (lang_code === 'zh') {
        lang_code = navigator.language;
        console.log(lang_code);
      }

      $.ajax({
        url: '/origins-translations/translation-link-ui/title/' + lang_code,
      })
        .done(function(data) {
          if (data) {
            $(elm).val(data);
          }
        });
    }
  }

  // Open the selected translation in a new tab.
  function viewTranslation(i, elm) {
    $(elm).change(function () {
      if ($(elm).val().length > 0) {
        window.open(('https://translate.google.com/translate?hl=en&tab=TT&sl=auto&tl=' + $(elm).val()));
      }
    });
  }

  Drupal.behaviors.originsTranslate = {
    attach: function (context, settings) {
      $('.origins-translation-link', context).once('origins-translation').each(disableLinkUi);
      $('.origins-translation-button', context).once('origins-translation').each(enableButtonUi);
      $('.origins-translation-select', context).once('origins-translation').each(viewTranslation);
    }
  };

})(jQuery, Drupal, drupalSettings);
