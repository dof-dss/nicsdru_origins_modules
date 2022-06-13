(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.operationShamrock = {
    attach: function attach (context) {
      $('body', context).once('shamrock').each(function () {
        $.getJSON(drupalSettings.origins_shamrock.service_url, function (data) {
          if (data.enabled) {
            $('body').prepend(data.banner);
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);

