(function ($, Drupal, once) {
  Drupal.behaviors.originsReporter = {
    attach: function (context, settings) {
      once('reporterUI', 'html').forEach(function (element) {
        $( "body" ).append('<div class="reporter-link"><span>Reporter an issue with this content</span></div>');
        $('.reporter-link').on('click', function() {
          let entityId = drupalSettings.origins_content_issue.entity_id;
          let revisionId = drupalSettings.origins_content_issue.revision_id;

          let config = {
            url: '/origins/content-issue/add/' + entityId + '/' + revisionId,
            dialogRenderer: 'off_canvas',
            dialogType: 'dialog',
            dialog: { width: 400 },
          };
          let modal = Drupal.ajax(config);
          modal.execute();
        } );
      })
    }
  }
})(jQuery, Drupal, once);
