/**
 * @file entity_browser.multi_step_display.js
 *
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviours related to selected entities.
   */
  Drupal.behaviors.entityBrowserMultiStepDisplay = {
    attach: function (context) {
      $(context).find('.entity-browser-form').each(function () {
        $(this).find('.selected-entities-list').sortable({
          stop: Drupal.entityBrowserMultiStepDisplay.entitiesReordered
        });
      });
    }
  };

  Drupal.entityBrowserMultiStepDisplay = {};

  /**
   * Reacts on sorting of the entities.
   *
   * @param event
   *   Event object.
   * @param ui
   *   Object with detailed information about the sort event.
   */
  Drupal.entityBrowserMultiStepDisplay.entitiesReordered = function(event, ui) {
    var items = $(this).find('.selected-item-container');
    for (var i = 0; i < items.length; i++) {
      $(items[i]).find('.weight').val(i);
    }
  };

}(jQuery, Drupal, drupalSettings));
