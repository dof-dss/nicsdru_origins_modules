(function ($, Drupal) {
  Drupal.behaviors.operationShamrock = {
    attach: function attach (context) {
      $('body', context).once('shamrock').each(function () {
        $.getJSON('/services/operation-shamrock.json', function (data) {
          if (data.enabled) {
            $('body').prepend(data.banner);
          }
        });
      });
    }
  };
})(jQuery, Drupal);

