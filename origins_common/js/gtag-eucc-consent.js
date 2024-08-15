/**
 * @file
 * gtag-eucc-consent.js
 *
 * EUCC consent handling for Google Consent Mode.
 */

(function (Drupal, drupalSettings) {

  // Initialise the dataLayer Google Tag Manager and gtag.js uses to
  // pass information to tags.
  window.dataLayer = window.dataLayer || [];

  // Initialise Drupal.eu_cookie_compliance to add event
  // handlers to it (see eu_cookie_compliance/README.md).
  Drupal.eu_cookie_compliance = Drupal.eu_cookie_compliance || function() {
    (Drupal.eu_cookie_compliance.queue = Drupal.eu_cookie_compliance.queue || []).push(arguments)
  };

  // Handler for EUCC status / preference change events. Push an
  // event to GTM container to indicate when EUCC preferences have been
  // loaded or changed.
  const euccConsentHandler = function(response) {

    window.cookieResponse = response;
    const status = response.currentStatus ? parseInt(response.currentStatus) : null;

    // Push event to indicate cookie choices made.
    if (status) {
      window.dataLayer.push({
        'event': 'eucc_preferences_completed'
      });
      console.log('dataLayer push eucc_preferences_completed');
    }
  }

  // Add handler to relevant EUCC events.
  Drupal.eu_cookie_compliance('postPreferencesLoad', euccConsentHandler);
  Drupal.eu_cookie_compliance('postStatusSave', euccConsentHandler);

})(Drupal, drupalSettings);
