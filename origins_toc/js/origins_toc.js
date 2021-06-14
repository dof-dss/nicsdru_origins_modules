/* eslint-disable */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.originsToC = {
    attach: function attach (context) {

      if (typeof drupalSettings.origins_toc.settings !== 'undefined') {
        var toc_settings = drupalSettings.origins_toc.settings;
      } else {
        return;
      }
      // Check if Toc is enabled for this entity type.
      if (toc_settings.toc_enable != 1) {
        return;
      }

      var tocHeadings = $(toc_settings.toc_source_container + ' > ' + toc_settings.toc_element, context).once('attachToC');
      var viewport_height = $(window).height();
      var source_container_height = $(toc_settings.toc_source_container).height();
      var text_screen_count = Math.round(source_container_height / viewport_height);

      // Display the ToC if the content area is longer or equal to the minimum screen depth.
      if (text_screen_count >= toc_settings.toc_screen_depth) {
        var tocHeadings = $(toc_settings.toc_source_container + ' ' + toc_settings.toc_element + ':not(:empty)').not(toc_settings.toc_exclusions);
        var $tocList = $('<ul class="nav-menu" />');
        var $headingText = Drupal.t(toc_settings.toc_title);
        var $skipTocText = Drupal.t('Skip table of contents');

        // Iterate each element, append an anchor id and append link to block list.
        $(tocHeadings, context).once('toc').each(function(index) {
          var $linkText = $(this).text();

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

        var $tocMain = $(toc_settings.toc_location);
        var $tocBlock = $('<nav class="sub-menu toc-menu" aria-labelledby="toc-menu-heading" />');

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

      if (toc_settings.toc_debug) {
        console.group(['Origins ToC debug information']);
        console.table({
          'Viewport height': viewport_height,
          'Source container height': source_container_height,
          'Content screens count': text_screen_count,
          'Screen depth requirement': parseInt(toc_settings.toc_screen_depth),
          'Source container' : toc_settings.toc_source_container,
          'Source element' : toc_settings.toc_element,
          'Source exclusions' : toc_settings.toc_exclusions,
          'Source element count' : tocHeadings.length,
        });
        console.groupEnd();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
