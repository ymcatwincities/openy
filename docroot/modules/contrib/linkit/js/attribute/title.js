/**
 * @file
 * Title attribute functions.
 */

(function ($, Drupal, document) {

  'use strict';

  var fieldName = '[name="attributes[title]"]';
    // var fieldName = '[name="attributes[uuid]"]';
    // var fieldName = '[name="attributes[entity_type_id]"]';

  /**
   * Automatically populate the title attribute.
   */
  $(document).bind('linkit.autocomplete.select', function (triggerEvent, event, ui) {
    if (ui.item.hasOwnProperty('title')) {
      $('form.linkit-editor-dialog-form').find(fieldName).val(ui.item.title);
    }
      if (ui.item.hasOwnProperty('uuid')) {
      // $('form.linkit-editor-dialog-form').find(fieldName).val(ui.item.title);
          console.log('hacked', ui.item.uuid);
      }
      if (ui.item.hasOwnProperty('entity_type_id')) {
          console.log('hacked', ui.item.entity_type_id);
      }
  });

})(jQuery, Drupal, document);
