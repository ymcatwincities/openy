/**
 * @file
 * Attaches the behaviors for the OpenY Campaign Color module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openy_campaign_color_frontend = {
    attach: function (context, settings) {
      $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: drupalSettings.openy_campaign_color.frontend.css
      }).appendTo("head");

      console.log(drupalSettings.openy_campaign_color.frontend.css);

    }
  };

})(jQuery, Drupal, drupalSettings);
