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

  // Disable the non-javascript link.
  function disableLinkUi(i, elm) {
    $(elm).addClass('hidden');
  }

  function enableMenuUi(i, elm) {
    let $button = $('.origins-translation-button', elm);
    let $menu = $('.origins-translation-menu', elm);

    // Initially menu is hidden, so ensure menu links are not
    // keyboard focusable.
    $menu.find('a').attr('tabindex', '-1');

    // Aria-expanded attribute on the button is used as
    // CSS hook to show/hide the menu and enable/disable
    // keyboard focus on menu links.
    $button
        .attr('aria-expanded', false)
        .removeClass('hidden')
        .click(function (e) {
          e.preventDefault();
          let expanded = $(this).attr('aria-expanded') === 'true' || false;
          let tabindex = expanded ? '-1' : '0';
          $(this).attr('aria-expanded', !expanded);
          $(this).parent('#origins-translation-container').toggleClass('top', !expanded);
          $menu.find('a').attr('tabindex', tabindex);
        });

    // If focus leaves the translation menu, it should close.
    $(elm).focusout(function () {
      if ($(this).is(':focus-within') !== true && $button.attr('aria-expanded') === 'true') {
        // Close it via the button.
        $button.click();
        // Ensure menu links cannot receive keyboard focus.
        $menu.find('a').attr('tabindex', '-1');
      }
    });

    // Translate bits of UI into user's preferred language.
    let $langListHeading = $(elm).find('h3');
    let lang_code = preferredLanguage().toLowerCase();

    if (lang_code.substring(0,2) !== 'en') {

      if (lang_code === 'zh') {
        // Assume simplified chinese.
        lang_code = 'zh-cn';
      }

      // Lookup the translation for the UI title.
      $.getJSON({
        url: '/origins-translations/translation-ui/languages',
      }).done(function(data) {
        if (data && data[lang_code]) {
          let buttonText = (data[lang_code][2])?? 'Translate this page';
          let langListLabel = (data[lang_code][3])?? 'Select a language';
          $button.text(buttonText);
          $langListHeading.text(langListLabel);
        }
      });
    }
  }

  // Update language links for current URL.
  function updateLinksUi(i, elm) {
    const pageUrl = new URL(location.href);

    $(elm).find('a').each(function () {
      let $link = new URL( $(this).attr('href') );
      $link.searchParams.set('u', pageUrl);
      $(this).attr('href', $link.href);
    });
  }

  Drupal.behaviors.originsTranslate = {
    attach: function (context, settings) {
      $(once('origins-translation', '.origins-translation-link', context)).each(disableLinkUi);
      $(once('origins-translation', '.origins-translation-container', context)).each(enableMenuUi);
      $(once('origins-translation', '.origins-translation-menu', context)).each(updateLinksUi);
    }
  };

  // Hide the translations button if we're on a translated page
  // (as it won't work).
  Drupal.behaviors.enableTranslationButton = {
    attach: function (context, settings) {
      if (location.hostname.indexOf("translate") >= 0) {
        $('.block-origins-translations').hide();
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
