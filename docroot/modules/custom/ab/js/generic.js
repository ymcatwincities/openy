/**
 * @file generic.js
 */
(function ($, Drupal, drupalSettings) {

    "use strict";

    /**
     * Attaches all registered behaviors to a page element with custom order:
     *   ab behaviour goes first, other behaviors go next.
     *
     * {@inheritdoc}
     */
    Drupal.attachBehaviors = function (context, settings) {
        context = context || document;
        settings = settings || drupalSettings;
        var behaviors = Drupal.behaviors;

        try {
            Drupal.behaviors.aB.attach(context, settings);
        }
        catch (e) {
            Drupal.throwError(e);
        }
        // Execute all of them.
        for (var i in behaviors) {
            if (i == 'aB') {
                continue;
            }
            if (behaviors.hasOwnProperty(i) && typeof behaviors[i].attach === 'function') {
                // Don't stop the execution of behaviors in case of an error.
                try {
                    behaviors[i].attach(context, settings);
                }
                catch (e) {
                    Drupal.throwError(e);
                }
            }
        }
    };

    /**
     * Registers behaviors related to blocks.
     */
    Drupal.behaviors.aB = {
        attach: function (context) {
            var cookie = $.cookie('ab');
            var navigationHome = $(document).find('.nav-home');
            var footerHome = $(document).find('.page-footer');

            if (cookie !== 'a' && cookie !== 'b') {
                cookie = Math.round(Math.random()) == 1 ? 'b' : 'a';
                $.cookie('ab', cookie);

                if(navigationHome.hasClass('nav-themes-b')){
                    navigationHome.removeClass('nav-themes-b');
                }

                if (footerHome.hasClass('footer-themes-b')) {
                    footerHome.removeClass('footer-themes-b');
                }
            }
            if (cookie == 'b' && drupalSettings['ab_state'] == 1) {
                navigationHome.addClass('nav-themes-b');
                footerHome.addClass('footer-themes-b');

                $.each(drupalSettings['ab'], function (index, value) {
                    $(context).find(value.selector).once('ab').each(function () {
                        $(this).replaceWith(value.html);
                    });
                });
            }
        }
    };

}(jQuery, Drupal, drupalSettings));
