/* eslint-disable */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.originsToC = {
    attach: function attach (context) {

      // Prevent duplication of ToC menu if it already exists.
      if ($('.toc-menu').length > 0) {
        return;
      }

      if (typeof drupalSettings.origins_toc.settings !== 'undefined') {
        var tocSettings = drupalSettings.origins_toc.settings;
      } else {
        return;
      }

      // Check if Toc is enabled for this entity type.
      if (tocSettings.toc_enable != 1) {
        return;
      }

      // Determine the number of scrollable screens for the toc source container on the users device.
      var viewportHeight = $(window).height();
      var sourceContainerHeight = $(tocSettings.toc_source_container).height();
      var textScreenCount = Math.round(sourceContainerHeight / viewportHeight);

      // Select all of the requested Heading elements within the source container which have content but excluding
      // those from the toc_exclusions query.
      var tocHeadings = $(tocSettings.toc_source_container + ' ' + tocSettings.toc_element + ':not(:empty)').not(tocSettings.toc_exclusions).once('attachToC');

      // Display the ToC if the content area is longer or equal to the minimum screen depth and contains more than 2
      // heading elements.
      if (textScreenCount >= tocSettings.toc_screen_depth && tocHeadings.length > 2) {
        var $tocLinkItems = $('<ul class="nav-menu" />');
        var $headingText = Drupal.t(tocSettings.toc_title);
        var $skipTocText = Drupal.t('Skip table of contents');

        // Iterate each element, append an anchor id and append link to block list.
        $(tocHeadings, context).once('toc').each(function(index) {
          var $linkText = $(this).text();

          // Ignore visually hidden elements.
          if ($(this).hasClass('visually-hidden')) {
            return;
          }

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
          $tocLinkItems.append(
            '<li class="nav-item"><a href="#toc-' + index + '">' + $linkText + '</a></li>'
          );
        });

        var $tocLocationElement = $(tocSettings.toc_location);
        var $tocMenu = $('<nav class="sub-menu toc-menu" aria-labelledby="toc-menu-heading" />');
        var $tocMenuSkip = '<a id="toc-menu-skip" href="#toc-menu-skip-target" class="skip-link visually-hidden focusable" aria-label="' + $skipTocText + '">' + $skipTocText + '</a>';
        var $tocMenuSkipTargetId = 'toc-menu-skip-target';

        $tocMenu.prepend(
          '<h2 id="toc-menu-heading" class="menu-title">' + $headingText + '</h2>',
          $tocMenuSkip,
          $tocLinkItems
        );

        if (tocSettings.toc_insert == 'before') {
          $tocLocationElement.before($tocMenu);
          $tocLocationElement.nextAll().wrapAll('<div id="' + $tocMenuSkipTargetId + '" tabindex="0" />');
        } else {
          $tocLocationElement.after($tocMenu);
          $tocMenu.nextAll().wrapAll('<div id="' + $tocMenuSkipTargetId + '" tabindex="0" />');
        }
      }

      if (tocSettings.toc_debug) {
        console.group(['Origins ToC debug information']);
        console.table({
          'Viewport height': viewportHeight,
          'Source container height': sourceContainerHeight,
          'Content screens count': textScreenCount,
          'Screen depth requirement': parseInt(tocSettings.toc_screen_depth),
          'Source container' : tocSettings.toc_source_container,
          'Source element' : tocSettings.toc_element,
          'Source exclusions' : tocSettings.toc_exclusions,
          'Source element count' : tocHeadings.length,
        });
        console.groupEnd();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
