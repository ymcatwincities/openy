/**
 * @file
 * ColumnButton2x plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('columnbutton2x', {
    init: function (editor) {

      editor.addCommand('columnbutton2x', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml( '<div class="cke-columns container"><div class="row"><div class="col-sm-6">{{ LEFT COLUMN CONTENT }}</div><div class="col-sm-6">{{ RIGHT COLUMN CONTENT }}</div></div></div>' );
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('ColumnButton2x', {
        label: Drupal.t('Columns 2x'),
        command: 'columnbutton2x',
        icon: this.path + '/columnbutton2x.png'
      });

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
