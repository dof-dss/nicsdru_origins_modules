/* eslint-disable */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.originsToC = {
    attach: function attach (context) {

      var tocHeadings = $('#main-article h2', context).once('attachToC');
      if (tocHeadings.length > 2) {

        if (typeof drupalSettings.origins_toc.settings !== 'undefined') {
          var toc_settings = drupalSettings.origins_toc.settings;
        } else {
          return;
        }

        // Check if Toc is enabled for this entity type and this entity.
        if (toc_settings.toc_enable != 1 || toc_settings.toc_entity_enable != 1) {
          return;
        }

        // This implementation doesn't use the configuration
        // from the toc 3rd party settings 'toc_settings'.
        let tocHeadings = $('#main-article h2').not('toc--exclude');
        let $tocList = $('<ul class="nav-menu" />');
        let $headingText = Drupal.t('Contents');
        let $skipTocText = Drupal.t('Skip table of contents');

        // Iterate each element, append an anchor id and append link to block list.
        $(tocHeadings, context).once('toc').each(function(index) {
          $(this).attr('id', 'toc-' + index);
          $tocList.append(
            '<li class="nav-item"><a href="#toc-' + index + '">' + $(this).text() + '</a></li>'
          );
        });

        let $tocMain = $('.page-summary');
        let $tocBlock = $('<nav class="sub-menu toc-menu" aria-labelledby="toc-menu-heading" />');
        $tocBlock.prepend('<h2 id="toc-menu-heading" class="menu-title">' + $headingText + '</h2>',
          '<a href="#toc-main-skip" class="skip-link visually-hidden focusable" aria-label="' + $skipTocText + '">' +
          $skipTocText +
          '</a>',
          $tocList);
        $tocMain.before($tocBlock,
          '<a id="toc-main-skip" tabindex="-1" class="visually-hidden" aria-hidden="true"></a>');
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
