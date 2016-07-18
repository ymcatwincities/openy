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
          editor.insertHtml('<div class="sports-promo cke-columns container"><div class="row"><div class="col-xs-12"><h3>{{ PLACEHOLDER <br> HEADER TITLE }}</h3><h4>{{ PLACEHOLDER SUBHEADER }}</h4><p>{{ PLACEHOLDER TEXT }}</p><a class="basketball sports-icon" href="/">{{ PLACEHOLDER LINK}}</a><div class="description">{{ PLACEHOLDER SUBLINK TEXT}}</div></div></div></div>');
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
