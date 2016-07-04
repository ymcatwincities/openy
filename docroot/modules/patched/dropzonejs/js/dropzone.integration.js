/**
 * @file dropzone.integration.js
 *
 * Defines the behaviors needed for dropzonejs integration.
 *
 * @todo Implement maxfilesexceeded.
 *
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.dropzonejsInstances = [];

  Drupal.behaviors.dropzonejsIntegraion = {
    attach: function (context) {
      Dropzone.autoDiscover = false;
      var selector = $(".dropzone-enable");
      selector.addClass("dropzone");
      var input = selector.siblings('input');

      // Initiate dropzonejs.
      var config = {
        url: input.attr('data-upload-path'),
        addRemoveLinks: true
      };
      var instanceConfig = drupalSettings.dropzonejs.instances[selector.attr('id')];
      if (instanceConfig.instance !== undefined) {
        instanceConfig.instance.destroy();
      }
      var dropzoneInstance = new Dropzone("#" + selector.attr("id"), $.extend({}, instanceConfig, config));

      // Other modules might need instances.
      drupalSettings["dropzonejs"]["instances"][selector.attr("id")]["instance"] = dropzoneInstance;

      // React on add file. Add only accepted files.
      dropzoneInstance.on("success", function (file, response) {
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value') || '';

        // The file is transliterated on upload. The element has to reflect
        // the real filename.
        file.processedName = response.result;

        uploadedFilesElement.attr('value', currentValue + response.result + ';');
      });

      // React on file removing.
      dropzoneInstance.on("removedfile", function (file) {
        var uploadedFilesElement = selector.siblings(':hidden');
        var currentValue = uploadedFilesElement.attr('value');

        // Remove the file from the element.
        if (currentValue.length) {
          var fileNames = currentValue.split(";");
          for (var i in fileNames) {
            if (fileNames[i] === file.processedName) {
              fileNames.splice(i,1);
              break;
            }
          }

          var newValue = fileNames.join(';');
          uploadedFilesElement.attr('value', newValue);
        }
      });
    }
  };


}(jQuery, Drupal, drupalSettings));
