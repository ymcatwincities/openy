(function ($) {
  "use strict";

  /**
   * Match Heights
   */
  Drupal.behaviors.openyMatchHeight = {
    attach: function (context, settings) {
      if (settings.matchheight) {
        if (settings.matchheight.selectors) {
          var selectors = settings.matchheight.selectors;
          // make them all equal heights.
          $.each(selectors, function (index, value) {
            $(value).matchHeight();
          });
        }
      }
    }
  };

})(jQuery);
