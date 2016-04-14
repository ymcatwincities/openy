
/**
 * @file
 * Renders a list of existing Block Content Types for selection.
 *
 * see Drupal.panels_ipe.BlockContentTypeCollection
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.BlockContentPicker = Drupal.panels_ipe.CategoryView.extend(/** @lends Drupal.panels_ipe.BlockContentPicker# */{

    /**
     * @type {function}
     */
    template_category: _.template(
      '<a class="ipe-block-content-type ipe-category<% if (active) { %> active<% } %>" data-category="<%- id %>">' +
      ' <div class="ipe-block-content-type-info">' +
      '   <h4><%- label %></h4>' +
      '   <p title="<%- description %>"><%- trimmed_description %></p>' +
      ' </div>' +
      '</a>'
    ),

    /**
     * @type {function}
     */
    template_form: _.template(
      '<h4>Add new <strong><%- label %></strong> content</h4>' +
      '<div class="ipe-block-type-form ipe-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
    ),

    /**
     * @type {function}
     */
    template_loading: _.template(
      '<span class="ipe-icon ipe-icon-loading"></span>'
    ),

    /**
     * @type {object}
     */
    events: {
      'click [data-category]': 'toggleBlockType'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {Object} options
     *   An object containing the following keys:
     * @param {Drupal.panels_ipe.BlockContentTypeCollection} options.collection
     *   An optional initial collection.
     */
    initialize: function (options) {
      if (options && options.collection) {
        this.collection = options.collection;
      }
    },

    /**
     * Renders the selection menu for picking Blocks.
     *
     * @return {Drupal.panels_ipe.BlockContentPicker}
     *   Return this, for chaining.
     */
    render: function () {
      // Empty ourselves.
      this.$el.html(this.template());

      // Initialize our BlockPluginCollection if it doesn't already exist.
      if (!this.collection) {
        var self = this;

        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a collection of block content types from the server.
        this.collection = new Drupal.panels_ipe.BlockContentTypeCollection();
        this.collection.fetch().done(function () {
          // We have a collection now, re-render ourselves.
          self.render();
        });

        return this;
      }

      // Note that the parent method renderCategories() is not called, as we
      // are only using the base View for its active logic and styling.
      this.collection.each(function (model) {
        var active = this.activeCategory == model.id;
        model.set('active', active);

        var template_vars = model.toJSON();

        // Reduce the length of the Block Content description if needed.
        template_vars.trimmed_description = template_vars.description;
        if (template_vars.trimmed_description.length > 30) {
          template_vars.trimmed_description = template_vars.description.substring(0, 30) + '...';
        }

        this.$('.ipe-categories').append(this.template_category(template_vars));
      }, this);

      // Check if a category is selected. If so, mark the top tray as active.
      if (this.activeCategory) {
        this.$('.ipe-category-picker-top').addClass('active');
      }

      return this;
    },

    /**
     * Informs the CategoryView of our form's callback URL.
     *
     * @param {Object} e
     *   The event object.
     *
     * @return {Object}
     *   An object containing the properties "url" and "model".
     */
    getFormInfo: function(e) {
      // Get the current block content type (which we store as the category).
      var type = $(e.currentTarget).data('category');

      var block_content_type = this.collection.get(type);
      var url = Drupal.panels_ipe.urlRoot(drupalSettings) + '/block_content/' + type + '/form';

      return {url: url, model: block_content_type};
    },

    /**
     * Overrides the default CategoryView toggleCategory method to display a
     * form instead of models related to a certain category.
     *
     * @param {Object} e
     *   The event object.
     */
    toggleBlockType: function(e) {
      this.toggleCategory(e);
      this.displayForm(e);
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
