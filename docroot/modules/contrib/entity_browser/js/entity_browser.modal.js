/**
 * @file entity_browser.modal.js
 *
 * Defines the behavior of the entity browser's modal display.
 */
(function ($, Drupal, drupalSettings) {
	
  "use strict";
  
  Drupal.AjaxCommands.prototype.select_entities = function (ajax, response, status) {
    var uuid = drupalSettings.entity_browser.modal.uuid;

    $('input[data-uuid="' + uuid + '"]').trigger('entities-selected', [uuid, response.entities])
    .removeClass('entity-browser-processed').unbind('entities-selected');
  };

  /**
   * Registers behaviours related to modal display.
   */
  Drupal.behaviors.entityBrowserModal = {
    attach: function (context) {
      _.each(drupalSettings.entity_browser.modal, function (instance) {
        _.each(instance.js_callbacks, function (callback) {
          // Get the callback.
          var callback = callback.split('.');
          var fn = window;

          for (var j = 0; j < callback.length; j++) {
            fn = fn[callback[j]];
          }

          if (typeof fn === 'function') {
            $('input[data-uuid="' +instance.uuid + '"]').not('.entity-browser-processed')
              .bind('entities-selected', fn).addClass('entity-browser-processed');
          }
        });
      });
    }
  }
}(jQuery, Drupal, drupalSettings));
