/**
 * @file
 * Modal dialog functionality with closing by timeout and redirects for Campaign login/logout process.
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    // Prevent pages being opening that require user being logged in.
    $('.login').each(function(ind, item) {
        if (!drupalSettings.openy_campaign.isLoggedIn) {
            $(item).on('click', function(e) {
               e.preventDefault();
               var campaignId = drupalSettings.openy_campaign.campaignId;
                Drupal.ajax({url: drupalSettings.path.baseUrl + 'campaign/login/' + campaignId}).execute();
            });
        }

    });

    // Add logged-in class to body.
    if (drupalSettings.openy_campaign.isLoggedIn && !$('body').hasClass('logged-in')) {
        $('body').addClass('logged-in');
    }

    // Replace URL query string - IE10+.
    $.fn.replaceQuery = function(fragment) {
        history.replaceState('', '', window.location.pathname + '?tab=' + fragment);
    };

    $.fn.closeDialogByClick = function() {
        $(".ui-widget-overlay").on('click', function (e) {
            $("#drupal-modal").dialog( "close" );
        });
    };

    // Custom function to close dialog and update user menu block.
    $.fn.closeDialog = function(queryParameter) {
        setTimeout(function(){
            // Close modal.
            $("#drupal-modal").dialog('close');

            // Redirect to campaign URL.
            var redirectPath = window.location.pathname;
            // Redirect to current parameter.
            var current = getParameterByName('tab');
            if (current) {
                redirectPath = window.location.pathname + '?tab=' + current;
            }
            // Redirect to given queryParameter.
            if (queryParameter) {
                redirectPath = window.location.pathname + '?tab=' + queryParameter;
                // Redirect to Campaign main page. Used with logout action.
                if (queryParameter === '<campaign-front>') {
                    redirectPath = window.location.origin + '/node/' + drupalSettings.openy_campaign.campaignId;
                }
            }

            window.location = redirectPath;
        }, 1000);
    };

    function getParameterByName(name) {
        var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
        return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
    }

})(jQuery, Drupal, drupalSettings);
