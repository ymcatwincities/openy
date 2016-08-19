/**
 * @file
 * Green3Columns plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('green_3_col', {
    init: function (editor) {

      editor.addCommand('green_3_col', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml('<div class="green-3-col cke-columns container"><div class="row"><div class="col-xs-12"><div class="row"><div class="col-sm-8 col-md-4"><h3>{{ TITLE PLACEHOLDER }}</h3></div></div></div><div class="col-sm-4"><p>{{ TEXT PLACEHOLDER }}</p></div><div class="col-md-offset-1 col-sm-4 col-md-3"> <h4>{{ LIST TITLE }}</h4><ul><li>{{ ITEM LIST 1 }}</li><li>{{ ITEM LIST 2 }}</li><li>{{ ITEM LIST 3 }}</li><li>{{ ITEM LIST 4 }}</li></ul><p class="button"><a href="{{ HREF }}">{{ LINK TITLE }}</a></p></div><div class="col-md-offset-1 col-sm-4 col-md-3"><h4>{{ LIST TITLE }}</h4><ul><li>{{ ITEM LIST 1 }}</li><li>{{ ITEM LIST 2 }}</li><li>{{ ITEM LIST 3 }}</li><li>{{ ITEM LIST 4 }}</li></ul><p class="button"><a href="{{ HREF }}">{{ LINK TITLE }}</a></p></div></div></div>');
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('Green3Columns', {
        label: Drupal.t('Green 3x columns'),
        command: 'green_3_col',
        icon: this.path + '/green_3_col.png'
      });

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
