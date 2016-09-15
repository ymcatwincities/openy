/**
 * @file file_browser.view.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */

  Drupal.behaviors.FileBrowserView = {
    attach: function (context) {
      $('.view-content').prepend('<div class="grid-sizer"></div><div class="gutter-sizer"></div>').once();
      $('.view-content').imagesLoaded(function () {
        $('.view-content').masonry({
          gutter: '.gutter-sizer',
          itemSelector: '.grid-item',
          percentPosition: true,
          isFitWidth:true
        });
      });

      // Determine field cardinality.
      var uuid = drupalSettings.path.currentQuery.uuid;
      var cardinality = parent.drupalSettings.entity_browser[uuid].cardinality;

      $('.grid-item').once('bind-click-event').click(function () {
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
