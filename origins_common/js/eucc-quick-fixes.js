/**
 * @file
 * Defines quick fix Javascript behaviors for EUCC cookie banner.
 */

(function($, Drupal) {
  'use strict';

  Drupal.behaviors.euccFocusFix = {
    attach: function (context, settings) {
      
      // Fix to ensure EUCC banner is compliant with WCAG 2.4.11 
      // "Focus Not Obscured".
      
      const $euccWrapper = $('#sliding-popup');
      const $euccToggle = $('button.eu-cookie-withdraw-tab', $euccWrapper);

      // If the banner is open and doesn't have keyboard focus...
      if ($('body').hasClass('eu-cookie-compliance-popup-open') && $euccWrapper.is(':focus-within') !== true) {
        // Focus on the banner's open/close toggle.
        $euccToggle.focus();
      }

      // Find focusable elements within the banner.
      $(document).on('click, focus', 'body', function(event) {
        // If the banner is open, but doesn't have keyboard focus...
        if ($('body').hasClass('eu-cookie-compliance-popup-open') && !$euccWrapper[0].contains(event.target)) {
          // Click open/close toggle to close it.
          $euccToggle.trigger('click');
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
