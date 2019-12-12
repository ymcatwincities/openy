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
      //After ajax response rendered input[name="step-3"] button is focused, we need to wait for that.
      var nextButton = $('input[name="step-3"]');
      if(nextButton.length) {
        nextButton.on('focus', function () {
          $('a[role="tab"][aria-selected=true]').focus();
        });
      } else {
        $('a[role="tab"][aria-selected=true]').focus();
      }
    }
  };

})(jQuery);
