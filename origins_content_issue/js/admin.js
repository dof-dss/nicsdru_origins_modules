(function ($, Drupal, once) {
  Drupal.behaviors.originsContentIssue = {

    displayIssue: function(entityId) {
      let endpoint = Drupal.url('origins/content-issue/display/' + entityId);
      Drupal.ajax({
        url: endpoint,
        progress: {
          type: 'none',
        },
      }).execute();
    },

    attach: function (context, settings) {

      once('issueRow', '.content-issue-row').forEach(function (element) {
        $(element).on('click', function() {
          let entityId = $(element).data('entity-id');
          Drupal.behaviors.originsContentIssue.displayIssue(entityId);
        });
      })

      once('issueClose', '.content-issue-layout-close').forEach(function (element) {
        $(element).on('click', function () {
          $('.content-issue-dashboard-aside').toggleClass('open');
        });
      });

    },
  }
})(jQuery, Drupal, once);
