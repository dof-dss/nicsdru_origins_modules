(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.operationShamrock = {
    attach: function attach (context) {
      $('body', context).once('shamrock').each(function () {
        $.getJSON(drupalSettings.origins_shamrock.service_url, function (data) {
          if (data.enabled) {
            let css_extra = '';
            if ('banner_extra_css' in drupalSettings.origins_shamrock) {
              css_extra = drupalSettings.origins_shamrock.banner_extra_css;
            }
            $('head').append('<style>' + data.styling + css_extra + '</style>');
            $('body').prepend(data.banner_html);
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);

