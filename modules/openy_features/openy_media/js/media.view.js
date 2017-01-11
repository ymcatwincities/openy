/**
 * @file media.view.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */

  Drupal.behaviors.MediaLibraryView = {
    attach: function (context, settings) {
      $('.item-container, .grid-item-library').css("display", "inline-block");
      $('.grid-item').once('bind-click-event').click(function () {
        var input = $(this).find('.views-field-entity-browser-select input');
        $("#entity-browser-media-embed-form input").prop("checked",false);
        $(".grid-item").removeClass('checked').find('.views-field-rendered-entity').css('opacity',1);
        input.prop('checked', !input.prop('checked'));
        var render;
        if (input.prop('checked')) {
          $(this).addClass('checked');
          render = $(this).find('.views-field-rendered-entity');
          $(render).css('opacity',0.3);
        }
        else {
          $(this).removeClass('checked');
          render = $(this).find('.views-field-rendered-entity');
          $(render).css('opacity',1);
        }
      });
    }
  };

}(jQuery, Drupal));
