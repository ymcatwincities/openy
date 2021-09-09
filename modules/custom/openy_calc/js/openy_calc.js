(function ($) {
  "use strict";

  /**
   * This will scroll the page when the alert box appears.
   */
  Drupal.behaviors.openy_calc_errors = {
    attach: function (context, settings) {
      $('.status-message__alert').each(function () {
        var divPosition = $('#membership-calc-wrapper').offset();
        // Reduce scroll by 100 to account for top nav. This fluctuates between the mobile and desktop sizes.
        $('html, body').animate({scrollTop: divPosition.top - 100}, "slow");
        $(this).focus();
      });
    }
  };

  /**
   * To close the alert Modals dialog.
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
