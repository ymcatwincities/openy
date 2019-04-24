(function ($) {
  "use strict";

  /**
   * Match Height on alerts.
   */
  Drupal.behaviors.alertsHeight = {
    attach: function (context, settings) {
      if (settings.path.currentPathIsAdmin !== true) {
        setTimeout(function () {
          $('[class^="alert"]', context).matchHeight();
        }, 1000);
      }
    }
  };

})(jQuery);