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
      });
    }
  };

  /**
   * This will focus current tab for aria.
   */
  Drupal.behaviors.openy_calc_focus = {
    attach: function (context, settings) {
      setTimeout(function() {
        $('a[role="tab"][aria-selected=true]').focus();
      }, 1000);
    }
  };

})(jQuery);
