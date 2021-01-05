/**
 * @file
 * Defines Javascript behaviors for moderation sidebar.
 */
(function ($, Drupal) {
  Drupal.behaviors.landinglisting = {
    attach: (context) => {
      $("a.archive-link").click(function(event) {
        return confirm("When archived, this content will be unpublished. Are you sure that you want to archive?");
      });
    }
  };
}(jQuery, Drupal));
