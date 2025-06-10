(function ($, Drupal, once) {
  Drupal.behaviors.originsContentIssue = {
    attach: function (context, settings) {

      once('issueOperations', 'div.link-button', context).forEach(function (element) {
        $(element).on('click', function () {
          alert($(element).data('operation') + ' id: ' + $(element).data('entity-id'));
        });
      });

      once('issueRow', '.content-issue-row').forEach(function (element) {
        $(element).on('click', function() {
          let entityId = $(element).data('entity-id');

          var ajaxSettings = {
            url: '/admin/content/content-issue/' + entityId,
            base: 'originsContentIssue',
            element: $(context).find('#content-issue-dashboard-aside'),
            progress: {
              type: 'none',
            },
          };

          var issueLoader = Drupal.ajax(ajaxSettings);

          issueLoader.commands.insert = function (ajax, response, status) {
            $('#content-issue-dashboard-aside article').replaceWith(response.data);
            $('#content-issue-dashboard-aside').addClass('open');
          };

          issueLoader.commands.destroyObject = function (ajax, response, status) {
            Drupal.ajax.instances[this.instanceIndex] = null;
          };

          issueLoader.execute();
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
