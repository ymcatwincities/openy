(function ($, Drupal) {
  "use strict";
  // Expand long location list for Event teaser view mode.
  Drupal.behaviors.expandLocations = {
    attach: function (context, settings) {
      $('.show-all-locations', context).click(function () {
        $(this).parent('.event-locations').find('.d-md-none').removeClass('d-md-none');
        $(this).removeClass('d-md-block');
      });
    }
  };

})(jQuery, Drupal);
