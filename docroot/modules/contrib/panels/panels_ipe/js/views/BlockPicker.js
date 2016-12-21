/**
 * @file
 * Renders a list of existing Blocks for selection.
 *
 * see Drupal.panels_ipe.BlockPluginCollection
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.BlockPicker = Drupal.panels_ipe.CategoryView.extend(/** @lends Drupal.panels_ipe.BlockPicker# */{

    /**
     * A selector to automatically click on render.
     *
     * @type {string}
     */
    autoClick: null,

    /**
     * @type {Drupal.panels_ipe.BlockPluginCollection}
     */
    collection: null,

    /**
     * @type {Drupal.panels_ipe.BlockContentTypeCollection}
     */
    contentCollection: null,

    /**
     * @type {function}
     */
    template_plugin: _.template(
      '<div class="ipe-block-plugin ipe-blockpicker-item">' +
      '  <a href="javascript:;" data-plugin-id="<%- plugin_id %>">' +
      '    <div class="ipe-block-plugin-info">' +
      '      <h5 title="<%- label %>"><%- trimmed_label %></h5>' +
      '    </div>' +
      '  </a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_content_type: _.template(
      '<div class="ipe-block-type ipe-blockpicker-item">' +
      '  <a href="javascript:;" data-block-type="<%- id %>">' +
      '    <div class="ipe-block-content-type-info">' +
      '      <h5 title="<%- label %>"><%- label %></h5>' +
      '      <p title="<%- description %>"><%- trimmed_description %></p>' +
      '    </div>' +
      '  </a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_create_button: _.template(
      '<a href="javascript:;" class="ipe-create-category ipe-category<% if (active) { %> active<% } %>" data-category="<%- name %>">' +
      '  <span class="ipe-icon ipe-icon-create_content"></span>' +
      '  <%- name %>' +
      '</a>'
    ),

    /**
     * @type {function}
     */
    template_form: _.template(
      '<% if (typeof(plugin_id) !== "undefined") { %>' +
      '<h4>' + Drupal.t('Configure <strong><%- label %></strong> block') + '</h4>' +
      '<% } else { %>' +
      '<h4>' + Drupal.t('Create new <strong><%- label %></strong> content') + '</h4>' +
      '<% } %>' +
      '<div class="ipe-block-form ipe-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
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
      'click .ipe-block-plugin [data-plugin-id]': 'displayForm',
      'click .ipe-block-type [data-block-type]': 'displayForm'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {Object} options
     *   An object containing the following keys:
     * @param {Drupal.panels_ipe.BlockPluginCollection} options.collection
     *   An optional initial collection.
     */
    initialize: function (options) {
      if (options && options.collection) {
        this.collection = options.collection;
      }

      this.on('tabActiveChange', this.tabActiveChange, this);

      // Extend our parent's events.
      _.extend(this.events, Drupal.panels_ipe.CategoryView.prototype.events);
    },

    /**
     * Renders the selection menu for picking Blocks.
     *
     * @return {Drupal.panels_ipe.BlockPicker}
     *   Return this, for chaining.
     */
    render: function () {
      var create_active = this.activeCategory === 'Create Content';

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
          name: 'Create Content',
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

    /**
     * Callback for our CategoryView, which renders an individual item.
     *
     * @param {Drupal.panels_ipe.BlockPluginModel} block_plugin
     *   The Block plugin that needs rendering.
     *
     * @return {string}
     *   The rendered block plugin.
     */
    template_item: function (block_plugin) {
      var template_vars = block_plugin.toJSON();

      // Not all blocks have labels, add a default if necessary.
      if (!template_vars.label) {
        template_vars.label = Drupal.t('No label');
      }

      // Reduce the length of the Block label if needed.
      template_vars.trimmed_label = template_vars.label;
      if (template_vars.trimmed_label.length > 30) {
        template_vars.trimmed_label = template_vars.label.substring(0, 30) + '...';
      }

      return this.template_plugin(template_vars);
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
    getFormInfo: function (e) {
      // Get the current plugin_id or type.
      var plugin_id = $(e.currentTarget).data('plugin-id');
      var url = Drupal.panels_ipe.urlRoot(drupalSettings);
      var model;

      // Generate a base URL for the form.
      if (plugin_id) {
        model = this.collection.get(plugin_id);
        url += '/block_plugins/' + plugin_id + '/form';
      }
      else {
        var block_type = $(e.currentTarget).data('block-type');

        model = this.contentCollection.get(block_type);
        url += '/block_content/' + block_type + '/form';
      }

      return {url: url, model: model};
    },

    /**
     * Fetches a collection from the server and re-renders the View.
     *
     * @param {string} type
     *   The type of collection to fetch.
     */
    fetchCollection: function (type) {
      var collection;
      var self = this;

      if (type === 'default') {
        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a collection of block plugins from the server.
        this.collection = new Drupal.panels_ipe.BlockPluginCollection();
        collection = this.collection;
      }
      else {
        // Indicate an AJAX request.
        this.$('.ipe-category-picker-top').html(this.template_loading());

        // Fetch a collection of block content types from the server.
        this.contentCollection = new Drupal.panels_ipe.BlockContentTypeCollection();
        collection = this.contentCollection;
      }

      collection.fetch().done(function () {
        // We have a collection now, re-render ourselves.
        self.render();
      });
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
