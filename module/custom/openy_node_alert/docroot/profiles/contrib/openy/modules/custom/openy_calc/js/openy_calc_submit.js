/**
 * Functions for after the form has been submitted.
 */
(function ($) {
  "use strict";

  /**
   * This will scroll the page up after the first step.
   */
  Drupal.behaviors.openy_calc_scroll = {
    attach: function (context, settings) {
      $('#membership-calc-wrapper').once().each(function () {
        var divPosition = $(this).offset();
        $('html, body').animate({scrollTop: divPosition.top - 100}, "slow");
        $(this).focus();
      });
    }
  };

})(jQuery);
