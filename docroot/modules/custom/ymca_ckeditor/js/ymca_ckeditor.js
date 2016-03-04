/**
 * @file
 * Alters CKEditor DTD.
 */

(function ($) {

  'use strict';

  /**
   * Alter CKEditor.dtd, allowing drupal-entity to be a content of any tag.
   */
  Drupal.behaviors.ymca_ckeditor = {

    /**
     * Editor attach callback.
     */
    attach: function (context, settings) {
      if (typeof(window.CKEDITOR) != 'undefined') {
        for (var i in window.CKEDITOR.dtd) {
          if (i.substring(0, 1) != '$') {
            window.CKEDITOR.dtd[i]['drupal-entity'] = 1;
          }
        }
      }
    }

  };

})(jQuery);
