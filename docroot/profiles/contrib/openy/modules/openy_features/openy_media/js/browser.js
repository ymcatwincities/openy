/**
 * @file browser.js
 */

(function ($, window, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.entityBrowserSelection = {

    attach: function (context) {
      // All selectable elements which should receive the click behavior.
      var $selectables = $('[data-selectable]', context);

      // Selector for finding the actual form inputs.
      var input = 'input[name ^= "entity_browser_select"]';

      $selectables.unbind("click").click(function() {
        // Allow unselecting and multiselect.
        if ($(this).hasClass('selected')) {
          $(this).removeClass('selected').find(input).prop('checked', false);
        } else {
          // Select this one...
          $(this).addClass('selected').find(input).prop('checked', true);
        }
      });
    }

  };

  Drupal.behaviors.changeOnKeyUp = {

    onKeyUp: _.debounce(function () {
      $(this).trigger('change');
    }, 600),

    attach: function (context) {
      $('.keyup-change', context).on('keyup', this.onKeyUp);
    },

    detach: function (context) {
      $('.keyup-change', context).off('keyup', this.onKeyUp);
    }

  };

})(jQuery, window, Drupal, drupalSettings);
