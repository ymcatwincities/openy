/**
 * @file
 * Renders a list of existing Blocks for selection.
 *
 * see Drupal.panels_ipe.BlockPluginCollection
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.OpenYDSBlockPicker = Drupal.panels_ipe.BlockPicker.extend(/** @lends Drupal.panels_ipe.OpenYDSBlockPicker# */{

    /**
     * Renders the selection menu for picking Blocks.
     *
     * @return {Drupal.panels_ipe.OpenYDSBlockPicker}
     *   Return this, for chaining.
     */
    render: function () {
      var create_active = this.activeCategory === 'Create Reusable Block';

      // Initialize our collections if they don't already exist.
      if (!this.collection) {
        this.fetchCollection('default');
        return this;
      }
      else if (create_active && !this.contentCollection) {
        this.fetchCollection('content');
        return this;
      }

      // Render our categories.
      this.renderCategories();

      // Add a unique class to our top region to scope CSS.
      this.$el.addClass('ipe-block-picker-list');

      // Prepend a custom button for creating content, if the user has access.
      if (drupalSettings.panels_ipe.user_permission.create_content) {
        this.$('.ipe-categories').prepend(this.template_create_button({
          name: 'Create Reusable Block',
          active: create_active
        }));
      }

      // If the create content category is active, render items in our top
      // region.
      if (create_active) {
        // Hide the search box.
        this.$('.ipe-category-picker-search').hide();

        this.contentCollection.each(function (block_content_type) {
          var template_vars = block_content_type.toJSON();

          // Reduce the length of the description if needed.
          template_vars.trimmed_description = template_vars.description;
          if (template_vars.trimmed_description.length > 30) {
            template_vars.trimmed_description = template_vars.description.substring(0, 30) + '...';
          }

          this.$('.ipe-category-picker-top').append(this.template_content_type(template_vars));
        }, this);
      }

      // Check if we need to automatically select one item.
      if (this.autoClick) {
        this.$(this.autoClick).click();
        this.autoClick = null;
      }

      this.trigger('render');

      // Focus on the current category.
      this.$('.ipe-category.active').focus();

      return this;
    },
  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
