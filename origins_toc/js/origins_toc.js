/* eslint-disable */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.originsToC = {
    attach: function attach (context) {

      if (typeof drupalSettings.origins_toc.settings !== 'undefined') {
        var toc_settings = drupalSettings.origins_toc.settings;
      } else {
        return;
      }

      // Check if Toc is enabled for this entity type and this entity instance.
      if (toc_settings.toc_enable != 1 || toc_settings.toc_entity_enable != 1) {
        return;
      }

      var tocHeadings = $(toc_settings.toc_source_container + ' ' + toc_settings.toc_element, context).once('attachToC');

      if (tocHeadings.length > 2) {

        let tocHeadings = $(toc_settings.toc_source_container + ' ' + toc_settings.toc_element).not(toc_settings.toc_exclusions);
        let $tocList = $('<ul class="nav-menu" />');
        let $headingText = Drupal.t(toc_settings.toc_title);
        let $skipTocText = Drupal.t('Skip table of contents');

        // Iterate each element, append an anchor id and append link to block list.
        $(tocHeadings, context).once('toc').each(function(index) {
          const $linkText = $(this).text();

          // Ignore the 'more useful links' section, if present.
          if ($linkText.toLowerCase() == 'more useful links') {
            return;
          }

          // Ignore empty source elements.
          if ($linkText.toLowerCase().trim().length == 0) {
            return;
          }

          // Build the ToC links.
          $(this).attr('id', 'toc-' + index);
          $tocList.append(
            '<li class="nav-item"><a href="#toc-' + index + '">' + $linkText + '</a></li>'
          );
        });


        let $tocMain = $(toc_settings.toc_location);
        let $tocBlock = $('<nav class="sub-menu toc-menu" aria-labelledby="toc-menu-heading" />');
        $tocBlock.prepend('<h2 id="toc-menu-heading" class="menu-title">' + $headingText + '</h2>',
          '<a href="#toc-main-skip" class="skip-link visually-hidden focusable" aria-label="' + $skipTocText + '">' +
          $skipTocText +
          '</a>',
          $tocList);

        if (toc_settings.toc_insert == 'before') {
          $tocMain.before($tocBlock, '<a id="toc-main-skip" tabindex="-1" class="visually-hidden" aria-hidden="true"></a>');
        } else {
          $tocMain.after($tocBlock, '<a id="toc-main-skip" tabindex="-1" class="visually-hidden" aria-hidden="true"></a>');
        }


      }

    }
  };
})(jQuery, Drupal, drupalSettings);
