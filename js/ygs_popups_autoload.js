(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.ygs_popups_autoload = {
    attach: function (context, settings) {
      // Open popup.
      var $dialog_link = $('a.popup-autostart');
      $dialog_link.click();
      $dialog_link.remove();
    }
  };

} (jQuery, Drupal, drupalSettings));
