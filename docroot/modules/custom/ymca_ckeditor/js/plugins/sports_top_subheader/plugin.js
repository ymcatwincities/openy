/**
 * @file
 * Sports Top Subheader plugin.
 *
 * @ignore
 */
(function($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('sports_top_subheader', {
    requires: 'widget',
    init: function(editor) {

      // Add widget
      editor.ui.addButton('SportsTopSubheader', {
        label: Drupal.t('Sports Top Subheader'),
        command: 'sports_top_subheader',
        icon: this.path + 'sports_top_subheader.png'
      });

      editor.widgets.add('sports_top_subheader', {
        template: '<div class="sports_top_subheader">' +
        '<div class="selectbox"><ul><li>Item 1</li></ul></div>' +
        '<div class="text"><p>Content...</p></div>' +
        '</div>',

        editables: {
          title: {
            selector: '.sports_top_subheader .selectbox',
            allowedContent: 'ul li a'
          },
          content: {
            selector: '.sports_top_subheader .text'
          }
        },

        allowedContent: 'div(!sports_top_subheader); div(!.sports_top_subheader .selectbox); div(!.sports_top_subheader .text);',

        requiredContent: 'div(sports_top_subheader)',

        upcast: function(element) {
          return element.name == 'div' && element.hasClass('sports_top_subheader');
        }
      });
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
