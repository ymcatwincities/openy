(function ($) {
  "use strict";

  /**
   * Add confirmation message after uninstall package form submit.
   */
  Drupal.behaviors.openy_confirm_form = {
    attach: function (context, settings) {
      $("#packages-uninstall-confirm").on('click', function ()  {
        return confirm(Drupal.t("Are you sure you want to uninstall packages? Data and content from within these packages will be lost."))
      });
    }
  };
})(jQuery);
