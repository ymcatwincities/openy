(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.youtubeModal = {
        attach: function (context, settings) {
            $(".youtube-link").grtyoutube(
                {
                    autoPlay: true,
                    theme: "dark" // or dark

                }
            );
        }
    };

})(jQuery, Drupal, drupalSettings);
