/**
 * @file
 * ColumnButton3x plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('columnbutton3x', {
    init: function (editor) {

      editor.addCommand('columnbutton3x', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml( '<div class="cke-columns container"><div class="row"><div class="col-sm-4">{{ LEFT COLUMN CONTENT }}</div><div class="col-sm-4">{{ CENTER COLUMN CONTENT }}</div><div class="col-sm-4">{{ RIGHT COLUMN CONTENT }}</div></div></div>' );
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('ColumnButton3x', {
        label: Drupal.t('Columns 3x'),
        command: 'columnbutton3x',
        icon: this.path + '/columnbutton3x.png'
      });

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
