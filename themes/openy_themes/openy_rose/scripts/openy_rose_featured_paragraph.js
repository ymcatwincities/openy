(function ($) {
  "use strict";
  Drupal.behaviors.openy_rose_featured_paragraph = {
    attach: function (context, settings) {
      function paragraphResponsive() {
        if ($(window).width() < 768) {
          if (!$('.wrapper-field-prgf-clm-description').hasClass('slick-slider')) {
            $('.wrapper-field-prgf-clm-description .row-eq-height').css('width', ($(window).width() - 50) + 'px');
            $('.wrapper-field-prgf-clm-description').slick({
              infinite: false,
              slidesToShow: 1,
              slidesToScroll: 1,
              variableWidth: true,
              centerMode: true,
              adaptiveHeight: true,
              dots: true,
              nextArrow: '<i class="slick-next slick-arrow fa fa-chevron-right" aria-hidden="true"></i>',
              prevArrow: '<i class="slick-prev slick-arrow fa fa-chevron-left" aria-hidden="true"></i>',
              responsive: [
                {
                  breakpoint: 767,
                  settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    touchThreshold: 10
                  }
                }
              ]
            });
          }
          else {
            $('.wrapper-field-prgf-clm-description .row-eq-height').css('width', ($(window).width() - 50) + 'px');
          }
        }
        else {
          if ($('.wrapper-field-prgf-clm-description').hasClass('slick-initialized')) {
            $('.wrapper-field-prgf-clm-description', context).slick('unslick');
            $('.wrapper-field-prgf-clm-description').css('width', '');
            $('.wrapper-field-prgf-clm-description .row-eq-height').css('width', '');
          }
        }
      }

      $(window).on('resize.paragraphResponsive', paragraphResponsive).trigger('resize.paragraphResponsive');
    }
  };
})(jQuery);