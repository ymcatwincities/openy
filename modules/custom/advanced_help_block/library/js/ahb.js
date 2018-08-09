(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.youtubeModal = {
        attach: function (context, settings) {
            $(".youtube-link").grtyoutube(
                {
                    autoPlay: true,
                    theme: "dark"
                }
            );
        }
    };

})(jQuery, Drupal, drupalSettings);
