(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.ygs_popups_autoload = {
    attach: function (context, settings) {
      var preferred_branch = $.cookie('ygs_preferred_branch');
      if (typeof preferred_branch !== 'undefined') {
        // Open popup.
        $('a.location-popup-link.popup-autostart').once().click();
        // $.cookie('ygs_preferred_branch', 6, { expires: 7, path: '/' });
      }
    }
  };

  // Prevent Class page location popup form from being submitted, instead of it
  // fires 'locations-changed' event and closes the dialog.
  Drupal.behaviors.ygs_popup_no_submit = {
    attach: function (context, settings) {
      $('.ygs-popups-class-branches-form', context).on('submit', function (e) {
        var location = $('[name=branch]:checked', this).val();
        $(document).trigger('location-changed', [{ location: location }]);
        $(this).parents('.ui-dialog-content').dialog('close');
        e.preventDefault();
      });
    }
  };

} (jQuery, Drupal, drupalSettings));
