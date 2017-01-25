(function ($) {
  "use strict";
  Drupal.behaviors.openy_rose_featured_paragraph = {
    attach: function (context, settings) {
      function paragraphResponsive() {
        if ($(window).width() < 768) {
          if (!$('.field-collection-item--name-field-fcontent-columns').hasClass('slick-slider')) {
            $('.field-collection-item--name-field-fcontent-columns .row-eq-height').css('width', ($(window).width() - 50) + 'px');
            $('.field-collection-item--name-field-fcontent-columns').slick({
              infinite: false,
              slidesToShow: 1,
              slidesToScroll: 1,
              variableWidth: true,
              centerMode: true,
              adaptiveHeight: true,
              dots: true,
              nextArrow: '<i class="slick-next slick-arrow fa fa-chevron-right"></i>',
              prevArrow: '<i class="slick-prev slick-arrow fa fa-chevron-left"></i>',
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
            $('.field-collection-item--name-field-fcontent-columns .row-eq-height').css('width', ($(window).width() - 50) + 'px');
          }
        }
        else {
          if ($('.field-collection-item--name-field-fcontent-columns').hasClass('slick-initialized')) {
            $('.field-collection-item--name-field-fcontent-columns', context).slick('unslick');
            $('.field-collection-item--name-field-fcontent-columns').css('width', 'auto');
            $('.field-collection-item--name-field-fcontent-columns .row-eq-height').css('width', 'auto');
          }
        }
      }

      $(window).on('resize.paragraphResponsive', paragraphResponsive).trigger('resize.paragraphResponsive');
    }
  };
})(jQuery);