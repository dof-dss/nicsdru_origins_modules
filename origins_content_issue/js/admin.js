(function ($, Drupal, once) {
  Drupal.behaviors.originsContentIssue = {
    attach: function (context, settings) {

      function displayIssue(entityId) {
        let endpoint = Drupal.url('origins/content-issue/display/' + entityId);
        Drupal.ajax({
          url: endpoint,
          progress: {
            type: 'none',
          },
        }).execute();
      }

      if (settings.displayIssue) {
        displayIssue();
      }

      once('issueRow', '.content-issue-row').forEach(function (element) {
        $(element).on('click', function() {
          let entityId = $(element).data('entity-id');
          displayIssue(entityId);
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
