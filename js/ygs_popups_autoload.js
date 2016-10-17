(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.ygs_popups_autoload = {
    attach: function (context, settings) {
      // Open popup.
      $('a.popup-autostart').once().click();
    }
  };

} (jQuery, Drupal, drupalSettings));
