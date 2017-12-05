(function ($) {
  "use strict";
  Drupal.behaviors.openy_hours_formatter = {
    attach: function (context, settings) {
      $('.today-hours .show-link').once().on('click', function(e) {
        e.preventDefault();
        $(this)
          .addClass('hidden')
          .parent()
          .find('.hide-link').removeClass('hidden')
          .parent()
          .find('.branch-hours').removeClass('hidden');
      });
      $('.today-hours .hide-link').once().on('click', function(e) {
        e.preventDefault();
        $(this)
          .addClass('hidden')
          .parent()
          .find('.show-link').removeClass('hidden')
          .parent()
          .find('.branch-hours').addClass('hidden');
      });
    }
  };
})(jQuery);
