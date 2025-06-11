(function ($, Drupal, once) {
  Drupal.behaviors.originsContentIssue = {
    attach: function (context, settings) {

      once('issueRow', '.content-issue-row').forEach(function (element) {
        $(element).on('click', function() {
          let entityId = $(element).data('entity-id');

          let endpoint = Drupal.url('origins/content-issue/display/' + entityId);
          Drupal.ajax({
            url: endpoint,
            element: '#content-issue-dashboard-aside article',
            progress: {
              type: 'none',
            },
          }).execute();

        });
      })

      once('issueClose', '.content-issue-layout-close').forEach(function (element) {
        $(element).on('click', function () {
          $('.content-issue-dashboard-aside').toggleClass('open');
        });
      });

    }
  }
})(jQuery, Drupal, once);
