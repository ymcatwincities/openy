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
     * @type {function}
     */
    template_plugin: _.template(
      '<div class="ipe-block-plugin">' +
      '  <div class="ipe-block-plugin-info">' +
      '    <h5><%- label %></h5>' +
      '    <p>Provider: <strong><%- provider %></strong></p>' +
      '  </div>' +
      '  <a data-plugin-id="<%- plugin_id %>">Add</a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_existing: _.template(
      '<div class="ipe-block-plugin">' +
      '  <div class="ipe-block-plugin-info">' +
      '    <h5><%- label %></h5>' +
      '    <p>Provider: <strong><%- provider %></strong></p>' +
      '  </div>' +
      '  <a data-existing-region-name="<%- region %>" data-existing-block-id="<%- uuid %>">Configure</a>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_form: _.template(
      '<h4>Configure <strong><%- label %></strong> block</h4>' +
      '<div class="ipe-block-plugin-form ipe-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
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
      'click .ipe-block-plugin [data-existing-block-id]': 'displayForm'
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
      var self = this;

      // Initialize our BlockPluginCollection if it doesn't already exist.
      if (!this.collection) {
        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a collection of block plugins from the server.
        this.collection = new Drupal.panels_ipe.BlockPluginCollection();
        this.collection.fetch().done(function () {
          // We have a collection now, re-render ourselves.
          self.render();
        });

        return this;
      }

      // Render our categories.
      this.renderCategories();

      // If we're viewing the current layout tab, show a custom item.
      var on_screen_count = 0;
      Drupal.panels_ipe.app.get('layout').get('regionCollection').each(function (region) {
        region.get('blockCollection').each(function (block) {
          if (self.activeCategory && self.activeCategory == 'On Screen') {
            block.set('region', region.get('name'));
            self.$('.ipe-category-picker-top').append(self.template_item(block));
          }
          ++on_screen_count;
        });
      });

      // Prepend on screen blocks to our collection.
      if (on_screen_count > 0) {
        this.$('.ipe-categories').prepend(this.template_category({
          name: 'On Screen',
          count: on_screen_count,
          active: this.activeCategory === 'On Screen'
        }));
      }

      // Check if we need to automatically select one item.
      if (this.autoClick) {
        this.$(this.autoClick).click();
        this.autoClick = null;
      }

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
    template_item: function(block_plugin) {
      // This is an existing block.
      if (block_plugin.get('uuid')) {
        return this.template_existing(block_plugin.toJSON());
      }
      else {
        return this.template_plugin(block_plugin.toJSON());
      }
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
      // Get the current plugin_id.
      var plugin_id = $(e.currentTarget).data('plugin-id');

      // Generate a base URL for the form.
      var layout_id = Drupal.panels_ipe.app.get('layout').get('id');
      var url = Drupal.panels_ipe.urlRoot(drupalSettings) + '/layout/' + layout_id + '/block_plugins/';

      var plugin;

      // This is a new block.
      if (plugin_id) {
        plugin = this.collection.get(plugin_id);
        url += plugin_id + '/form';
      }
      // This is an existing block.
      else {
        // Get the Block UUID and Region Name
        var block_id = $(e.currentTarget).data('existing-block-id');
        var region_name = $(e.currentTarget).data('existing-region-name');

        // Get the Block plugin
        plugin = Drupal.panels_ipe.app.get('layout').get('regionCollection')
          .get(region_name).get('blockCollection').get(block_id);
        plugin_id = plugin.get('id');

        url += plugin_id + '/block/' + block_id + '/form';
      }

      return {url: url, model: plugin};
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
