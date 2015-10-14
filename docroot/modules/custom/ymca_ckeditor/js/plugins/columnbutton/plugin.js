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
      console.log("hello!");
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
