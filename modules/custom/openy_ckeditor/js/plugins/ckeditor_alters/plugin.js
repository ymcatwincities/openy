/**
 * @file
 * CKEditor plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('ckeditor_alters', {
    init: function (editor) {
      editor.on('instanceReady', function(ck) {

        // Update the label of "Increase" and "Decrease" indent buttons.
        if (ck.editor.commands.hasOwnProperty('indent')) {
          $('.cke_button__indent').attr('title', Drupal.t('Increase Indent for list item'));
        }

        if (ck.editor.commands.hasOwnProperty('outdent')) {
          $('.cke_button__outdent').attr('title', Drupal.t('Decrease Indent for list item'));
        }
      });
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
