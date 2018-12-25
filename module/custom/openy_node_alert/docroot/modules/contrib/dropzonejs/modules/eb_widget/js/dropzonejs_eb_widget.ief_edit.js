/**
 * @file
 * dropzonejs_eb_widget.ief_edit.js
 *
 * Bundles various dropzone eb widget behaviours.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dropzonejsPostIntegrationEbWidgetEditJs = {
    attach: function (context) {
      if (typeof drupalSettings.dropzonejs.instances !== 'undefined') {
        _.each(drupalSettings.dropzonejs.instances, function (item) {
          var $form = $(item.instance.element).parents('form');

          if ($form.hasClass('dropzonejs-disable-submit')) {
            var $submit = $form.find('.is-entity-browser-submit');
            $submit.prop('disabled', false);

            var autoSubmitDropzone = function () {
              var $form = this;

              // Trigger generation of IEF form only, when there are new
              // accepted files and there are no rejected files.
              if (item.instance.getAcceptedFiles().length > 0 && item.instance.getRejectedFiles().length === 0) {
                $('#edit-edit', $form).trigger('mousedown');

                item.instance.removeAllFiles();
              }
            }.bind($form);

            item.instance.on('queuecomplete', function () {
              autoSubmitDropzone();
            });

            item.instance.on('removedfile', function () {
              autoSubmitDropzone();
            });
          }
        });
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
