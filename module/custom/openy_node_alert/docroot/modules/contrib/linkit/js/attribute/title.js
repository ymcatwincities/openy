/**
 * @file
 * Title attribute functions.
 */

(function ($, Drupal, document) {

    'use strict';

    var fieldName = '[name="attributes[title]"]';
    var fieldNameUuid = '[name="attributes[data-drupal-entity-uuid]"]';
    var fieldNameEntityTypeId = '[name="attributes[data-drupal-entity-type-id]"]';

    /**
     * Automatically populate the title attribute.
     */
    $(document).bind('linkit.autocomplete.select', function (triggerEvent, event, ui) {
        if (ui.item.hasOwnProperty('title')) {
            $('form.linkit-editor-dialog-form').find(fieldName).val(ui.item.title);
        }
        if (ui.item.hasOwnProperty('linkit_entity_uuid')) {
            $('form.linkit-editor-dialog-form').find(fieldNameUuid).val(ui.item.linkit_entity_uuid);
        }
        if (ui.item.hasOwnProperty('entity_type_id')) {
            $('form.linkit-editor-dialog-form').find(fieldNameEntityTypeId).val(ui.item.entity_type_id);
        }
    });

})(jQuery, Drupal, document);
