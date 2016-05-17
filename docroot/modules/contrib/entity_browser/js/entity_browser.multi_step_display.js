/**
 * @file entity_browser.multi_step_display.js
 *
 */
(function ($, Drupal) {

  'use strict';

  /**
   * Registers behaviours related to selected entities.
   */
  Drupal.behaviors.entityBrowserMultiStepDisplay = {
    attach: function (context) {
      $(context).find('.entities-list').sortable({
        stop: Drupal.entityBrowserMultiStepDisplay.entitiesReordered
      });
    }
  };

  Drupal.entityBrowserMultiStepDisplay = {};

  /**
   * Reacts on sorting of the entities.
   *
   * @param {object} event
   *   Event object.
   * @param {object} ui
   *   Object with detailed information about the sort event.
   */
  Drupal.entityBrowserMultiStepDisplay.entitiesReordered = function (event, ui) {
    var items = $(this).find('.item-container');
    for (var i = 0; i < items.length; i++) {
      $(items[i]).find('.weight').val(i);
    }
  };

}(jQuery, Drupal));
