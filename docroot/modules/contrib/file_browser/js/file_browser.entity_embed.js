/**
 * @file file_browser.entity_embed.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to Entity Embed integrations.
   */
  Drupal.behaviors.FileBrowserEntityEmbed = {
    attach: function (context) {
      // Add an event handler that triggers a click inside the iFrame when our
      // duped element is clicked.
      $('.entity-browser-modal-submit').once('entity-browser-modal').click(function (e) {
        $('.entity-embed-dialog iframe').contents().find('.entity-browser-modal-target').click();
        e.preventDefault();
        e.stopPropagation();
      });

      // On iFrame load, hide the real nested "Select Files" button.
      $('body').once('entity-browser-modal').on('entityBrowserIFrameAppend', function () {
        $(this).find('.entity-embed-dialog iframe').load(function () {
          $(this).contents().find('.entity-browser-modal-target').parent().hide();
        });
      });
    }
  };

}(jQuery, Drupal));