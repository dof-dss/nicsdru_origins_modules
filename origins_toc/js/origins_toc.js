(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.originsToc = {
    attach: function attach(context) {
      var $context = $(context);
      var settings = drupalSettings.origins_toc.settings;

      var $toc_elements = $context.find(settings.toc_source_container + ' ' + settings.toc_element);

      $toc_elements.each(function(index) {
        $(this).attr('id', 'toc-' + index);
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
