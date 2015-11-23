/**
 * @file
 * Provides JavaScript additions to the managed file field type.
 *
 * This file provides progress bar support (if available), popup windows for
 * file previews, and disabling of other file fields during Ajax uploads (which
 * prevents separate file fields from accidentally uploading files).
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach behaviors to links within managed file elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.image_widget_crop = {
    attach: function (context, settings) {

      /**
       * Get all positions of elements to crop.
       */
      var commonCropElements = {
        edit: settings.path.currentPath.search('edit'),
        cropX1: $('.crop-x1'),
        cropY1: $('.crop-y1'),
        cropX2: $('.crop-x2'),
        cropY2: $('.crop-y2'),
        cropW: $('.crop-crop-w'),
        cropH: $('.crop-crop-h'),
        cropThumbW: $('.crop-thumb-w'),
        cropThumbH: $('.crop-thumb-h')
      };

      /**
       * Get all needed coordinates to construct crop.
       *
       * @param {object} element - The current element wrapper of image to crop.
       * @param {object} commonCropElements - All positions Coordinates elements.
       *
       * @return {object} An object with all element used by Plugins.
       */
      function getCropCoordinates(element, commonCropElements) {
        return {
          posx1: $(element).find(commonCropElements.cropX1),
          posy1: $(element).find(commonCropElements.cropY1),
          posx2: $(element).find(commonCropElements.cropX2),
          posy2: $(element).find(commonCropElements.cropY2),
          cropw: $(element).find(commonCropElements.cropW),
          croph: $(element).find(commonCropElements.cropH),
          saved_img: $(element).find('img'),
          w: $(element).find(commonCropElements.cropThumbW),
          h: $(element).find(commonCropElements.cropThumbH),
          dataRatioName: $(element).attr('id')
        };
      }

      $('section.ratio-list:not(.crop-processed)').addClass('crop-processed').each(function () {
        // On click in list element.
        $(this).find('ul li').on('click', function (event) {
          event.preventDefault();

          // Get elements.
          var ElementRatio = $(this).data('ratio');
          var ElementName = $(this).data('name');
          var wrapperCropContainer = $(this).closest('.crop-wrapper').find('.preview-wrapper-crop #' + ElementName);
          var defaultValues = getCropCoordinates(wrapperCropContainer, commonCropElements);
          var img = wrapperCropContainer.find('img');

          // On click delete all active class.
          $('.ratio-list ul li').removeClass('active');

          // Active only this li.
          $(this).addClass('active');

          // Hide all element to show correct slide in next step.
          $(this).closest('.crop-wrapper').find('section.preview-wrapper-crop div.crop-preview-wrapper-list').hide();
          // Initialize plugin.
          wrapperCropContainer.show();
          wrapperCropContainer.addClass('active');

          if (commonCropElements.edit > -1 && $(this).hasClass('saved')) {
            // Only in edit context get all element for this clicked element.
            var savedElements = $(this).closest('.crop-wrapper').find('.preview-wrapper-crop > .crop-preview-wrapper-list');
            savedElements.each(function (i, item) {
              if ($(item).hasClass('saved')) {
                var cropSaved = getCropCoordinates(item, commonCropElements);
                // Initialize ImageAreaSelect Plugin object.
                var crop = $(cropSaved.saved_img).imgAreaSelect({
                  instance: true,
                  keys: true
                });
                crop.setSelection(cropSaved.posx1.val(), cropSaved.posy1.val(), cropSaved.posx2.val(), cropSaved.posy2.val());
                crop.setOptions({
                  aspectRatio: $(item).closest('.crop-wrapper').find('ul li').data('ratio'),
                  keys: true,
                  handles: true,
                  movable: true,
                  parent: $(this),
                  minWidth: 50,
                  minHeight: 50,
                  onSelectEnd: function (saved_img, selection) {

                    // Calculate X1 / Y1 position of crop zone.
                    $(cropSaved.posx1).val(selection.x1);
                    $(cropSaved.posy1).val(selection.y1);

                    // Calculate X2 / Y2 position of crop zone.
                    $(cropSaved.posx2).val(selection.x2);
                    $(cropSaved.posy2).val(selection.y2);

                    // Calculate width / height size of crop zone.
                    $(cropSaved.cropw).val(selection.width);
                    $(cropSaved.croph).val(selection.height);

                    // Get size of thumbnail in UI.
                    $(cropSaved.w).val(cropSaved.saved_img.width);
                    $(cropSaved.h).val(cropSaved.saved_img.height);

                    $('#' + cropSaved.dataRatioName).find('input.delete-crop').val('0');
                  }
                });
              }
              else {
                // Stick cliked element for add class when user crop picture.
                var listElement = $(this);

                // Create an crop instance.
                var cropInstance = $(img).imgAreaSelect({
                  instance: true,
                  keys: true
                });

                // Set options.
                cropInstance.setOptions({
                  aspectRatio: ElementRatio,
                  parent: wrapperCropContainer,
                  handles: true,
                  movable: true,
                  minWidth: 50,
                  minHeight: 50,
                  onSelectEnd: function (img, selection) {

                    // Calculate X1 / Y1 position of crop zone.
                    $(defaultValues.posx1).val(selection.x1);
                    $(defaultValues.posy1).val(selection.y1);

                    // Calculate X2 / Y2 position of crop zone.
                    $(defaultValues.posx2).val(selection.x2);
                    $(defaultValues.posy2).val(selection.y2);

                    // Calculate width / height size of crop zone.
                    $(defaultValues.cropw).val(selection.width);
                    $(defaultValues.croph).val(selection.height);

                    // Get size of thumbnail in UI.
                    $(defaultValues.w).val(img.width);
                    $(defaultValues.h).val(img.height);
                    // If user clic in crop zone not save it.
                    if (selection.width > 0 || selection.height > 0) {
                      $('#' + defaultValues.dataRatioName).find('input.delete-crop').val('0');

                      // When user have crop the selection mark saved.
                      $(listElement).addClass('saved');
                    }
                  },
                  x1: defaultValues.posx1.val(),
                  y1: defaultValues.posy1.val(),
                  x2: defaultValues.posx2.val(),
                  y2: defaultValues.posy2.val()
                });
              }
            });
          }
          else {
            // Stick cliked element for add class when user crop picture.
            var listElement = $(this);

            // Create an crop instance.
            var cropInstance = $(img).imgAreaSelect({instance: true, keys: true});

            // Set options.
            cropInstance.setOptions({
              aspectRatio: ElementRatio,
              parent: wrapperCropContainer,
              handles: true,
              movable: true,
              minWidth: 50,
              minHeight: 50,
              onSelectEnd: function (img, selection) {

                // Calculate X1 / Y1 position of crop zone.
                $(defaultValues.posx1).val(selection.x1);
                $(defaultValues.posy1).val(selection.y1);

                // Calculate X2 / Y2 position of crop zone.
                $(defaultValues.posx2).val(selection.x2);
                $(defaultValues.posy2).val(selection.y2);

                // Calculate width / height size of crop zone.
                $(defaultValues.cropw).val(selection.width);
                $(defaultValues.croph).val(selection.height);

                // Get size of thumbnail in UI.
                $(defaultValues.w).val(img.width);
                $(defaultValues.h).val(img.height);
                // If user clic in crop zone not save it.
                if (selection.width > 0 || selection.height > 0) {
                  $('#' + defaultValues.dataRatioName).find('input.delete-crop').val('0');

                  // When user have crop the selection mark saved.
                  $(listElement).addClass('saved');
                }
              },
              x1: defaultValues.posx1.val(),
              y1: defaultValues.posy1.val(),
              x2: defaultValues.posx2.val(),
              y2: defaultValues.posy2.val()
            });
          }
        });
      });

      // Add saved class if the crop have been processed before user has add an item.
      $('section.ratio-list.crop-processed').closest('.crop-wrapper').find('section.preview-wrapper-crop > .crop-preview-wrapper-list:not(#crop-help)').each(function (i, item) {
        var cropSaved = getCropCoordinates(item, commonCropElements);
        if (cropSaved.cropw.val() > 0 && cropSaved.croph.val() > 0) {
          $(item).closest('.crop-wrapper').find('.ratio-list ul li[data-name*=\'' + $(item).attr('id') + '\']').addClass('saved');
        }
      });

      $('.delete').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        $(this).parents('li').removeClass('saved active');
        var dataRatioName = $(this).closest('.crop-preview-wrapper').data('name');
        $(this).closest('.crop-wrapper').find('#' + dataRatioName).removeClass('active');
        $(this).closest('.crop-wrapper').find('#' + dataRatioName + ' .crop-preview-wrapper-value input').removeAttr('value');
        $(this).closest('.crop-wrapper').find('#' + dataRatioName + ' input.delete-crop').val('1');
        $(this).closest('.crop-wrapper').find('#' + dataRatioName).hide();
        $(this).closest('.crop-wrapper').find('#crop-help').show();
        // If you have an slide active not show help slide.
        if ($(this).closest('.crop-wrapper').find('.crop-preview-wrapper-list.active').length) {
          $(this).closest('.crop-wrapper').find('#crop-help').hide();
        }
        // Create an crop instance.
        $(this).closest('.crop-wrapper').find('#' + dataRatioName + ' img').imgAreaSelect({hide: true});
      });
    }
  };

})(jQuery, Drupal);
