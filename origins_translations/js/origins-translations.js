/**
 * @file
 * Attaches behaviors for the Origins Translations module.
 */

(function($, Drupal) {
  'use strict';

  const preferredLanguage = function () {
    let nav = window.navigator,
      browserLanguagePropertyKeys = ['language', 'browserLanguage', 'systemLanguage', 'userLanguage'],
      i,
      language;

    // support for HTML 5.1 "navigator.languages"
    if (Array.isArray(nav.languages)) {
      for (i = 0; i < nav.languages.length; i++) {
        language = nav.languages[i];
        if (language && language.length) {
          return language;
        }
      }
    }

    // support for other well known properties in browsers
    for (i = 0; i < browserLanguagePropertyKeys.length; i++) {
      language = nav[browserLanguagePropertyKeys[i]];
      if (language && language.length) {
        return language;
      }
    }

    return null;
  };
  console.log(preferredLanguage());

  // Disable the non-javascript link.
  function disableLinkUi(i, elm) {
    $(elm).addClass('hidden');
  }

  // Enable the AJAX button and update title.
  function translateUi(i, elm) {

    let $button = $('.origins-translation-button');

    $button
      .attr('aria-expanded', false)
      .removeClass('hidden')
      .click(function (e) {
        e.preventDefault();
        let expanded = $(this).attr('aria-expanded') === 'true' || false;
        $(this).attr('aria-expanded', !expanded);
      });

    let $langListHeading = $(elm).find('h3');
    let lang_code = preferredLanguage();

    if (lang_code !== 'en') {
      // Allow for Simplified (zh-cn) and Traditional (zh-tw) Chinese.
      if (lang_code === 'zh') {
        lang_code = navigator.language;
      }

      // Lookup the translation for the UI title.
      $.getJSON({
        url: '/origins-translations/translation-ui/languages',
      }).done(function(data) {
        if (data) {
          $button.text(data[lang_code][3]);
          $langListHeading.text(data[lang_code][4]);
        }
      });
    }
  }

  // Update language links for current URL.
  function updateLinksUi(i, elm) {
    const pageUrl = new URL(location.href);

    $(elm).find('a').each(function () {
      let $link = new URL( $(this).attr('href') );
      $link.searchParams.set('u', encodeURIComponent(pageUrl));
      $(this).attr('href', $link.href);
    });
  }

  Drupal.behaviors.originsTranslate = {
    attach: function (context, settings) {
      $('.origins-translation-link', context).once('origins-translation').each(disableLinkUi);
      $('.origins-translation-container', context).once('origins-translation').each(translateUi);
      $('.origins-translation-list', context).once('origins-translation').each(updateLinksUi);
    }
  };

})(jQuery, Drupal, drupalSettings);
