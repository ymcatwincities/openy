/**
 * @file
 * HF Javascript routines.
 */

/**
 * @file generic.js
 */
(function ($, Drupal, drupalSettings) {

    "use strict";

    /**
     * Registers behaviors related to headerless-footerless architecture.
     */
    Drupal.behaviors.openy_hf = {
        attach: function (context) {
            var query = getUrlVars();

            function renderHeader() {
                console.log('header');
                $.each(drupalSettings['openy_hf.header_replacements'], function (index, value) {
                    $(context).find(value.selector).attr("style", "visibility: visible;");
                });
            }

            function renderFooter() {
                console.log('footer');
                $.each(drupalSettings['openy_hf.footer_replacements'], function (index, value) {
                    $(context).find(value.selector).attr("style", "visibility: visible;");
                });
            }

            function getUrlVars() {
                var vars = [], hash;
                var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                for (var i = 0; i < hashes.length; i++) {
                    hash = hashes[i].split('=');
                    vars.push(hash[0]);
                    vars[hash[0]] = hash[1];
                }
                return vars;
            }

            // Display previously hidden replacements.
            if (query['dnr']) {
                switch (query['dnr']) {
                    case 'hf':
                        return;
                    case 'h':
                        renderFooter();
                        break;
                    case 'f':
                        renderHeader();
                        break;
                    default:
                        renderFooter();
                        renderHeader();
                        return;
                }
            } else {
                renderFooter();
                renderHeader();
            }


        }
    };

}(jQuery, Drupal, drupalSettings));

