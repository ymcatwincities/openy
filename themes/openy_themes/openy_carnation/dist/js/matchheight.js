(function ($) {
  "use strict";

  /**
   * Match Heights
   */
  Drupal.behaviors.openyMatchHeight = {
    attach: function (context, settings) {
      var selectors = settings.matchheight.selectors;
      // make them all equal heights.
      $.each(selectors, function (index, value) {
        $(value).matchHeight();
      });
    }
  };

})(jQuery);
