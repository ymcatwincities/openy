/**
 * @file
 * ListBranchAmenities plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('list_branch_amenities', {
    init: function (editor) {
      editor.addCommand('list_branch_amenities', {
        canUndo: true,
        exec: function (editor) {
          editor.insertHtml('[openy:list-branch-amenities]');
        }
      });

      // Add buttons for link and unlink.
      editor.ui.addButton('ListBranchAmenities', {
        label: Drupal.t('List of Branch Amenities'),
        command: 'list_branch_amenities',
        icon: this.path + '/icon.png'
      });
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
