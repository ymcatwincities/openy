/**
 * @file ymca_alters.blocks.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviors related to blocks.
   */
  Drupal.behaviors.Blocks = {
    attach: function (context) {
      $('.view-content-browser-block.view-display-id-entity_browser_1 .views-row').equalize('height');
    }
  };
}(jQuery, Drupal, drupalSettings));