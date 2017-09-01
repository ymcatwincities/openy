(function ($, Drupal, drupalSettings) {

    'use strict';

    // Replace URL query string - IE10+
    $.fn.replaceQuery = function(fragment) {
        history.replaceState('', '', window.location.pathname + '?tab=' + fragment);
    };

    // Custom function to close dialog and update user menu block
    $.fn.closeDialog = function(queryParameter) {
        setTimeout(function(){
            // Close modal
            $("#drupal-modal").dialog('close');

            // Redirect to campaign URL
            var redirectPath = window.location.pathname;
            // Redirect to current parameter
            var current = getParameterByName('tab');
            if (current) {
                redirectPath = window.location.pathname + '?tab=' + current;
            }
            // Redirect to given queryParameter
            if (queryParameter) {
                redirectPath = window.location.pathname + '?tab=' + queryParameter;
                // Redirect to Campaign main page. Used with logout action.
                if (queryParameter === '<campaign-front>') {
                    redirectPath = window.location.pathname;
                }
            }

            window.location = redirectPath;
        }, 3000);
    };

    function getParameterByName(name) {
        var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
        return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
    }

})(jQuery, Drupal, drupalSettings);