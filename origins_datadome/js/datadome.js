/**
 * @file
 * Adds DataDome values to the browser window object.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  let ddkey = drupalSettings.origins_datadome?.ddkey;
  let ddconfig = drupalSettings.origins_datadome?.ddconfig;

  if (!ddkey) {
    console.log("Warning: Datadome key is empty.");
  } else {
    window.ddjskey = ddkey;

    if (ddconfig) {
      window.ddoptions = ddconfig;
    }
  }

})(Drupal, drupalSettings);

