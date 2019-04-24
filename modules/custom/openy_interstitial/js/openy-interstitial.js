(function ($, Drupal, drupalSettings) {

    'use strict';
    
    Drupal.behaviors.openyInterstitialBlock = {
        attach: function (context) {
            // Get values from Interstitial page node
            var time = drupalSettings.openyInterstitial.InterstitialContentBlock.time;
            var interaction = drupalSettings.openyInterstitial.InterstitialContentBlock.interaction;
            var dialogSelector = '#interstitial-block';

            // Check and update Cookie
            if (isShowDialog() === false) {
                $('#block-interstitialcontentblock').hide();
                return;
            }

            // Create new dialog
            $(dialogSelector).dialog({
                title: drupalSettings.openyInterstitial.InterstitialContentBlock.title,
                closeOnEscape: false,
                draggable: false,
                modal: true,
                width: 800,
            });

            // If time set an interaction = 0 - close dialog after time.
            if (time > 0 && interaction == 0) {
                // Hide close button. TODO Add with CSS display: none; to ".no-close .ui-dialog-titlebar-close" class
                $(dialogSelector).dialog('option', 'dialogClass', 'no-close');

                // Close dialog after set time
                setTimeout(function () {
                    $(dialogSelector).dialog('close');
                }, time * 1000);
            }

            // If only interaction set - close dialog by Close button and click anywhere outside of the modal.
            if (interaction && time < 1) {
                // Close dialog by click outside of it.
                outsideClickCloseDialog(dialogSelector);
            }

            // If both set - activate close buttons after time seconds
            if (time > 0 && interaction) {
                // Hide close button. TODO Add with CSS display: none; to ".no-close .ui-dialog-titlebar-close" class
                $(dialogSelector).dialog('option', 'dialogClass', 'no-close');

                // Activate Close actions after set time
                setTimeout(function () {
                    // Show Close button
                    $('.ui-widget-content').removeClass('no-close');
                    $('.ui-dialog-titlebar-close').show();

                    // Enable close dialog by click outside of it.
                    outsideClickCloseDialog(dialogSelector);
                }, time * 1000);
            }

            /**
             * Close dialog by click anywhere outside of it.
             *
             * @param dialogSelector Dialog selector.
             */
            function outsideClickCloseDialog(dialogSelector) {
                $('body').bind('click',function(e){
                    if(
                        $(dialogSelector).dialog('isOpen')
                        && !$(e.target).is('.ui-dialog, a')
                        && !$(e.target).closest('.ui-dialog').length
                    ){
                        $(dialogSelector).dialog('close');
                    }
                });
            }

            function isShowDialog() {
                var current = $.cookie('OpenYInterstitialBlock') * 1;
                if (!current) {
                    current = 1;
                }

                var showTimes = drupalSettings.openyInterstitial.InterstitialContentBlock.showTimes * 1;
                if (current > showTimes) {
                    return false;
                }

                $.cookie('OpenYInterstitialBlock', current + 1);
                return true;
            }
        }
    };

})(jQuery, Drupal, drupalSettings);
