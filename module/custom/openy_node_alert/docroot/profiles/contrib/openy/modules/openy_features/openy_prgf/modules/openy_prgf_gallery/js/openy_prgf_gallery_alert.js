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

  Drupal.behaviors.carousel_pause = {
    attach: function (context, settings) {

      $('.paragraph--type--gallery', context).hover(function(){
        $(this).find('.carousel').carousel('pause');
      },function(){
        $(this).find('.carousel').carousel('cycle');
      });

      $('.carousel .carousel-control').focus(function(){
        $(this).parent().carousel('pause');
      }).blur(function() {
        $(this).parent().carousel('cycle');
      });

    }
  };

})(jQuery);
