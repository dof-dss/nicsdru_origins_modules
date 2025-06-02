(function ($, Drupal, once) {
  Drupal.behaviors.originsReporter = {
    attach: function (context, settings) {
      once('reporterUI', 'html').forEach(function (element) {
        $( "body" ).append('<div class="reporter-link"><span>Reporter an issue with this content</span></div>');
        $('.reporter-link').on('click', function() {
          var config = {
            url: '/origins-reporter/report-form',
            dialogRenderer: 'off_canvas',
            dialogType: 'dialog',
            dialog: { width: 400 },
          };
          var modal = Drupal.ajax(config);
          modal.execute();
        } );
      })
    }
  }
})(jQuery, Drupal, once);
