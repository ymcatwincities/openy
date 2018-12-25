/**
 * @file
 * Attaches the behaviors for the Open Y Campaign Color module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Displays farbtastic color selector and initialize color administration UI.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach color selection behavior to relevant context.
   */
  Drupal.behaviors.openy_campaign_color_backend = {
      attach: function (context, settings) {

          /**
           * Initiate color picker.
           */
          function init() {
            var colorSelector = form.find('#edit-scheme');
            var schemes = settings.color.schemes;
            var colorScheme = colorSelector.val();
            if (colorScheme !== '' && schemes[colorScheme]) {
              // Get colors of active scheme.
              colors = schemes[colorScheme];
              for (var fieldName in colors) {
                if (colors.hasOwnProperty(fieldName)) {
                  callback($('#edit-palette-' + fieldName), colors[fieldName], true);
                }
              }
            }
          }

          var colors;
          // This behavior attaches by ID, so is only valid once on a page.
          var form = $(context).find('#openy_campaign_color_color_scheme_form');
          if (form.length === 0) {
              return;
          }

          init();
          // Set up colorScheme selector.
          form.find('#edit-scheme').on('change', function () {
            init();
          });

          /**
           * Callback for Farbtastic when a new color is chosen.
           *
           * @param {HTMLElement} input
           *   The input element where the color is chosen.
           * @param {string} color
           *   The color that was chosen through the input.
           * @param {bool} propagate
           *   Whether or not to propagate the color to a locked pair value
           * @param {bool} colorScheme
           *   Flag to indicate if the user is using a color scheme when changing
           *   the color.
           */
          function callback(input, color, colorScheme) {
              var matched;
              // Set background/foreground colors.
              $(input).css({
                  backgroundColor: color,
              });

              // Change input value.
              if ($(input).val() && $(input).val() !== color) {
                  $(input).val(color);

                  // Reset colorScheme selector.
                  if (!colorScheme) {
                      resetScheme();
                  }
              }
          }

          /**
           * Resets the color scheme selector.
           */
          function resetScheme() {
              form.find('#edit-scheme').each(function () {
                  this.selectedIndex = this.options.length - 1;
              });
          }
      }
  }

})(jQuery, Drupal);
