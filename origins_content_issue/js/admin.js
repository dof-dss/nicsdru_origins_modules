(function ($, Drupal, once) {
  Drupal.behaviors.originsReporter = {
    attach: function (context, settings) {

      once('issueRow', '.content-issue-row').forEach(function (element) {
        $(element).on('click', function() {
          let entityId = $(element).data('entity-id');

          var ajaxSettings = {
            url: '/admin/content/content-issue/' + entityId,
            base: 'myBase',
            element: $(context).find('#content-issue-dashboard-aside')
          };

          var myAjaxObject = Drupal.ajax(ajaxSettings);

          myAjaxObject.commands.insert = function (ajax, response, status) {
            $('#content-issue-dashboard-aside article').replaceWith(response.data)
            $('#content-issue-dashboard-aside').addClass('open')
          };

          myAjaxObject.commands.destroyObject = function (ajax, response, status) {
            Drupal.ajax.instances[this.instanceIndex] = null;
          };

          myAjaxObject.execute();
        });
      })
    }
  }
})(jQuery, Drupal, once);
