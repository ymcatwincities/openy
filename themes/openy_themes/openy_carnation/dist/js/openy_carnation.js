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

  /**
   * Alert Modals close
   */
  Drupal.behaviors.openyAlertModalsClose = {
    attach: function (context, settings) {
      $('.alert-modal .close').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.alert-modal').remove();
      });
    }
  };
})(jQuery);
