(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.operationShamrock = {
    attach: function attach (context) {
      $('body', context).once('shamrock').each(function () {
        $.getJSON(drupalSettings.origins_shamrock.service_url, function (data) {
          if (data.enabled) {
            $('head').append(data.styling);
            $('body').prepend(data.banner_html);
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);

