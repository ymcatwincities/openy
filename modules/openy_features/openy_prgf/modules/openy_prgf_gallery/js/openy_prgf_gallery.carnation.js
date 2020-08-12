(function ($) {
    "use strict";

    Drupal.behaviors.carousels_indicators = {
        attach: function (context, settings) {
            // add relation between indicator and carousel.
            let i = 1;
            $('.carousel-indicators', context).each(function () {
                $(this).find('li').each(function () {
                    $(this).attr('data-target', '#carousel' + i);
                });
                i++;
            });

            // set active thumbnail on click.
            $('.carousel-indicators').find(function () {
                $(this).find('li').click(function () {
                    $('.carousel-indicators .active').removeClass('active');
                    $(this).addClass('active');
                });
            });

            // change active thumbnail on arrow click.
            $('.carousel').on('slid.bs.carousel', function (event) {
                let id = $(this).attr('id');
                $('li[data-target="#' + id + '"][data-slide-to="' + event.from + '"]').removeClass('active');
                $('li[data-target="#' + id + '"][data-slide-to="' + event.to + '"]').addClass('active');
            });

        }
    };

    Drupal.behaviors.carousels_swipe = {
        attach: function (context, settings) {
            $(".paragraph--type--gallery").on("touchstart", function(event){
                let xClick = event.originalEvent.touches[0].pageX;
                $(this).one("touchmove", function(event){
                    let xMove = event.originalEvent.touches[0].pageX;
                    if( Math.floor(xClick - xMove) > 5 ){
                        $(this).find('.carousel').carousel('next');
                    }
                    else if( Math.floor(xClick - xMove) < -5 ){
                        $(this).find('.carousel').carousel('prev');
                    }
                });
                $(this).on("touchend", function(){
                    $(this).off("touchmove");
                });
            });

        }
    };
})(jQuery);
