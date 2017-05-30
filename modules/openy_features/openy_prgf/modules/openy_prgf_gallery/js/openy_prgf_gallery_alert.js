(function ($) {
  "use strict";
  Drupal.behaviors.carousels_alert = {
    attach: function (context, settings) {
      var i = 1;
      $('.carousel', context).each(function () {
        $(this).attr('id', 'carousel' + i);
        $(this).find('.carousel-control').each(function () {
          $(this).attr('href', '#carousel' + i);
        });
        i++;
      });
    }
  };

})(jQuery);
