/**
 * @file
 * dropzonejs_eb_widget.common.js
 *
 * Bundles various dropzone eb widget behaviours.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dropzonejsPostIntegrationEbWidgetCommon = {
    attach: function (context) {
      if (typeof drupalSettings.dropzonejs.instances !== 'undefined') {
        _.each(drupalSettings.dropzonejs.instances, function (item) {
          var $form = $(item.instance.element).parents('form');

          if ($form.hasClass('dropzonejs-disable-submit')) {
            var $submit = $form.find('.is-entity-browser-submit');
            $submit.prop('disabled', true);

            item.instance.on('queuecomplete', function () {
              if (item.instance.getRejectedFiles().length === 0) {
                $submit.prop('disabled', false);
              }
              else {
                $submit.prop('disabled', true);
              }
            });

            item.instance.on('removedfile', function () {
              if (item.instance.getRejectedFiles().length === 0) {
                $submit.removeAttr('disabled');
              }

              // If there are no files in DropZone -> disable Button.
              if (item.instance.getAcceptedFiles().length === 0) {
                $submit.prop('disabled', true);
              }
            });

            if (drupalSettings.entity_browser_widget && drupalSettings.entity_browser_widget.auto_select) {
              item.instance.on('queuecomplete', function () {
                var dzInstance = item.instance;
                var filesInQueue = dzInstance.getQueuedFiles();
                var acceptedFiles;
                var i;

                if (filesInQueue.length === 0) {
                  acceptedFiles = dzInstance.getAcceptedFiles();

                  // Ensure that there are some files that should be submitted.
                  if (acceptedFiles.length > 0 && dzInstance.getUploadingFiles().length === 0) {
                    // First submit accepted files and clear them from list of
                    // dropped files afterwards.
                    $form.find('[id="auto_select_handler"]')
                      .trigger('auto_select_enity_browser_widget');

                    // Remove accepted files -> because they are submitted.
                    for (i = 0; i < acceptedFiles.length; i++) {
                      dzInstance.removeFile(acceptedFiles[i]);
                    }
                  }
                }
              });
            }

          }
        });
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
