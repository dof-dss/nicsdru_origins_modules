(function ($) {
  'use strict';

  Drupal.behaviors.inPageNavigation = {
    attach: function (context, settings) {

      const ipn_ids = Object.keys(settings.origins_common.in_page_navigation)

      for (let [key, value] of Object.entries(ipn_ids)) {
        var source = settings.origins_common.in_page_navigation[value].source;
        var element = settings.origins_common.in_page_navigation[value].element;
        var exclusions = settings.origins_common.in_page_navigation[value].exclusions;

        exclusions = exclusions.split(',');

        // Iterate each element, append an anchor id and append link to block list.
        $('#' + source + ' ' + element + ':not(' + exclusions + ')').each(function(index){
          $(this).attr('id', 'ipn-' + index);
          $('#block-' + value + ' div.content ul').append('<li><a href="#ipn-' + index + '">' + $(this).text() + '</a></li>');
        });
      }
    }
  };

}(jQuery));
