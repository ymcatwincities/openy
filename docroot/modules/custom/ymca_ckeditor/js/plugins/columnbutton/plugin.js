/**
 * @file
 * ColumnButton plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('columnbutton', {
    init: function (editor) {

      editor.addCommand('columnbutton', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml( '<div class="cke-columns clearfix" style="width:100%;"><div class="column-left" style="width:50%;float:left;">{{ LEFT COLUMN CONTENT }}</div><div class="column-right" style="width:50%;float:right;">{{ RIGHT COLUMN CONTENT }}</div></div>' );
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('ColumnButton', {
        label: Drupal.t('Columns'),
        command: 'columnbutton',
        icon: this.path + '/columnbutton.png'
      });

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
