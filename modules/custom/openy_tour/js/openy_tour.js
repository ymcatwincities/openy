(function ($) {
  "use strict";
  Drupal.openy_tour = Drupal.openy_tour || {};

  Drupal.behaviors.openy_tour = {
    attach: function (context, settings) {
      Drupal.openy_tour.click_button();
    }
  };

  Drupal.openy_tour.click_button = function () {
    $('.openy-click-button').on('click', function (e) {
      e.preventDefault();
      var selector = $(this).data('tour-selector');
      // Click on link if class/id is provided.
      $(selector).trigger('click');
      // Click on input if data selector is provided.
      $('input[data-drupal-selector="' + selector + '"]').trigger('mousedown');
    });
  };

})(jQuery);
