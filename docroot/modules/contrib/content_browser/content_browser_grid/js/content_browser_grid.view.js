/**
 * @file content_browser_grid.view.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.ContentBrowserGridView = {
    attach: function (context) {
      $('.view-content').masonry({
        itemSelector: '.views-row',
        columnWidth: 300,
        gutter: 15,
        isFitWidth:true
      });
    }
  };

}(jQuery, Drupal));
