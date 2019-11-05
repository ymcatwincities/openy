/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";
  Drupal.openyCarnation = Drupal.openyCarnation || {};

  /**
   * Alert Modals
   */
  Drupal.behaviors.openyAlertModals = {
    attach: function (context, settings) {
      var alertModals = $('.alert-modal', context);

      $(window).on('load', function () {
        if (alertModals.length) {
          alertModals.modal('show');
        }
      });
    }
  };
})(jQuery);
