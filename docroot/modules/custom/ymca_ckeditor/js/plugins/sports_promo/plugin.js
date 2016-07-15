/**
 * @file
 * SportsPromo plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('sports_promo', {
    init: function (editor) {

      editor.addCommand('sports_promo', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml( '<div class="cke-columns container"><div class="row"><div class="col-sm-4">{{ LEFT COLUMN CONTENT }}</div><div class="col-sm-4">{{ CENTER COLUMN CONTENT }}</div><div class="col-sm-4">{{ RIGHT COLUMN CONTENT }}</div></div></div>' );
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('SportsPromo', {
        label: Drupal.t('Sports Promo'),
        command: 'sports_promo',
        icon: this.path + '/sports_promo.png'
      });

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
