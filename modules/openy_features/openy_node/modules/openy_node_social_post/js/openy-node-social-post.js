(function ($) {
    "use strict";
    Drupal.behaviors.masonryGridBuilder = {
        attach: function (context, settings) {
            $('.grid').masonry({
                itemSelector: '.grid-item',
                columnWidth: '33%',
                percentPosition: true
            });

        }
    };
})(jQuery);