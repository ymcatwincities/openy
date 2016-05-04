/**
 * @file ymca_alters.content_browser.view.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.ContentBrowserView = {
    attach: function (context) {
      // Determine field cardinality.
      var uuid = drupalSettings.path.currentQuery.uuid;
      var cardinality = parent.drupalSettings.entity_browser[uuid].cardinality;

      $('.views-row').once('bind-click-event').click(function () {
        // Special handling for cardinality = 1.
        if (cardinality === 1) {
          $(this).siblings().each(function() {
            var input = $(this).find('.views-field-entity-browser-select input');
            input.prop('checked', false);
            $(this).removeClass('checked');
          });
        }

        var input = $(this).find('.views-field-entity-browser-select input');
        input.prop('checked', !input.prop('checked'));
        if (input.prop('checked')) {
          $(this).addClass('checked');
        }
        else {
          $(this).removeClass('checked');
        }
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
