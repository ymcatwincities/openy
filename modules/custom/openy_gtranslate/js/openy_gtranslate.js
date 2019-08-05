/**
 * @file openy_gtranslate.js
 */
(function ($, Drupal, drupalSettings) {

    "use strict";

    Drupal.behaviors.openy_gtranslate = {
        attach: function (context, settings) {
            setTimeout(function () {
                var langSelect = $('.goog-te-menu-frame').first();

                $('nav .navbar-nav li.language > a').on('click', function (e, context) {
                    e.preventDefault();
                    langSelect.show();
                    langSelect.addClass('open');
                    return false;
                });

                $('body').on('click', function (e, context) {
                    if (langSelect.hasClass('open')) {
                        langSelect.hide();
                        langSelect.removeClass('open');
                    }
                });
            }, 100);
        }
    };

    Drupal.behaviors.openy_gtranslate_mobile = {
        attach: function (context, settings) {
            // Emulate mobile width.
            if ($(window).width() < 768) {
                setTimeout(function() {
                    if ($('.navbar-nav > li.language .openy-google-translate').length == 0) {
                        $('.navbar-nav > li.language a').hide();
                        jQuery('#g_translater').detach().appendTo('.navbar-nav > li.language');
                    }
                }, 100);
            }

        }
    };

}(jQuery, Drupal, drupalSettings));