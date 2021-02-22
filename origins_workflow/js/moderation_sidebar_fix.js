/**
 * @file
 * Defines Javascript behaviors for moderation sidebar.
 */
(function ($, Drupal) {
  Drupal.behaviors.landinglisting = {
    attach: function (context) {
      $("a.archive-link").click(function(event) {
        /*
        A class of 'archive-link' means that this link has been faked by code in
        origins_workflow_moderation_sidebar_alter, so we complete the illusion
        by adding a confirmation here in the same way that moderation sidebar
        normally would.
         */
        return confirm(Drupal.t("When archived, this content will be unpublished. Are you sure that you want to archive?"));
      });
    }
  };
}(jQuery, Drupal));
