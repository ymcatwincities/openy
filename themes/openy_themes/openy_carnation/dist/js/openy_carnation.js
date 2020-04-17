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

      if (alertModals.length) {
        alertModals.on('hidden.bs.modal', function (e) {
          $(this).remove();
        });
        alertModals.modal('show');
      }
    }
  };
})(jQuery);
