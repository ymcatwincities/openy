(function ($) {
  "use strict";
  Drupal.behaviors.openy_rose_blog_slider = {
    attach: function (context, settings) {
      function blogResponsive() {
        if ($(window).width() < 768) {
          if (!$('.slick-mobile').hasClass('slick-slider')) {
            $('.slick-mobile').slick({
              infinite: true,
              slidesToShow: 1,
              slidesToScroll: 1,
              variableWidth: false,
              centerMode: false,
              dots: true,
              adaptiveHeight: false,
              nextArrow: '<i class="slick-next slick-arrow fa fa-chevron-right" aria-hidden="true"></i>',
              prevArrow: '<i class="slick-prev slick-arrow fa fa-chevron-left" aria-hidden="true"></i>',
            });
          }
        }
        else {
          if ($('.slick-mobile').hasClass('slick-initialized')) {
            $('.slick-mobile', context).slick('unslick');
            $('.slick-mobile').css('width', '');
          }
        }
      }

      $(window).on('resize.blogResponsive', blogResponsive).trigger('resize.blogResponsive');
    }
  };
})(jQuery);
