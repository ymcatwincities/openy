/**
 * @file entity_browser.common.js
 *
 * Common helper functions used by various parts of entity browser.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.entityBrowser = {};

  /**
   * Reacts on "entities selected" event.
   *
   * @param {object} event
   *   Event object.
   * @param {string} uuid
   *   Entity browser UUID.
   * @param {array} entities
   *   Array of selected entities.
   */
  Drupal.entityBrowser.selectionCompleted = function (event, uuid, entities) {
    var added_entities_array = $.map(entities, function (item) {return item[0];});
    // @todo Use uuid here. But for this to work we need to move eb uuid
    // generation from display to eb directly. When we do this, we can change
    // \Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReference::formElement
    // also.

    // Checking if cardinality is set - assume unlimited.
    var cardinality = isNaN(parseInt(drupalSettings['entity_browser'][uuid]['cardinality'])) ? -1 : parseInt(drupalSettings['entity_browser'][uuid]['cardinality']);

    // Having more elements than cardinality should never happen, because
    // server side authentication should prevent it, but we handle it here
    // anyway.
    if (cardinality !== -1 && added_entities_array.length > cardinality) {
      added_entities_array.splice(0, added_entities_array.length - cardinality);
    }

    // Update value form element with new entity IDs.
    var selector = drupalSettings['entity_browser'][uuid]['selector'] ? $(drupalSettings['entity_browser'][uuid]['selector']) : $(this).parent().parent().find('input[type*=hidden]');
    var entity_ids = selector.val();
    if (entity_ids.length !== 0) {
      var existing_entities_array = entity_ids.split(' ');

      // We always trim the oldest elements and add the new ones.
      if (cardinality === -1 || existing_entities_array.length + added_entities_array.length <= cardinality) {
        existing_entities_array = _.union(existing_entities_array, added_entities_array);
      }
      else {
        if (added_entities_array.length >= cardinality) {
          existing_entities_array = added_entities_array;
        }
        else {
          existing_entities_array.splice(0, added_entities_array.length);
          existing_entities_array = _.union(existing_entities_array, added_entities_array);
        }
      }

      entity_ids = existing_entities_array.join(' ');
    }
    else {
      entity_ids = added_entities_array.join(' ');
    }

    selector.val(entity_ids);
    selector.trigger('entity_browser_value_updated');
  };

  /**
   * Reacts on "entities selected" event.
   *
   * @param {object} element
   *   Element to bind on.
   * @param {array} callbacks
   *   List of callbacks.
   * @param {string} event_name
   *   Name of event to bind to.
   */
  Drupal.entityBrowser.registerJsCallbacks = function (element, callbacks, event_name) {
    // JS callbacks are registred as strings. We need to split their names and
    // find actual functions.
    for (var i = 0; i < callbacks.length; i++) {
      var callback = callbacks[i].split('.');
      var fn = window;

      for (var j = 0; j < callback.length; j++) {
        fn = fn[callback[j]];
      }

      if (typeof fn === 'function') {
        $(element).bind(event_name, fn);
      }
    }
  };

}(jQuery, Drupal, drupalSettings));


