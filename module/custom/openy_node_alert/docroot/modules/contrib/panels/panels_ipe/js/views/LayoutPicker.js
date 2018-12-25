/**
 * @file
 * Renders a collection of Layouts for selection.
 *
 * see Drupal.panels_ipe.LayoutCollection
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutPicker = Drupal.panels_ipe.CategoryView.extend(/** @lends Drupal.panels_ipe.LayoutPicker# */{

    /**
     * @type {function}
     */
    template_form: _.template(
      '<h4>' + Drupal.t('Configure <strong><%- label %></strong> layout') + '</h4>' +
      '<div class="ipe-layout-form ipe-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
    ),

    /**
     * @type {function}
     */
    template_layout: _.template(
    '<a href="javascript:;" class="ipe-layout" data-layout-id="<%- id %>">' +
    '  <img class="ipe-layout-image" src="<%- icon %>" title="<%- label %>" alt="<%- label %>" />' +
    '  <span class="ipe-layout-label"><%- label %></span>' +
    '</a>'
    ),

    /**
     * @type {function}
     */
    template_loading: _.template(
      '<span class="ipe-icon ipe-icon-loading"></span>'
    ),

    /**
     * @type {Drupal.panels_ipe.LayoutCollection}
     */
    collection: null,

    /**
     * @type {object}
     */
    events: {
      'click [data-layout-id]': 'displayForm'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {Object} options
     *   An object containing the following keys:
     * @param {Drupal.panels_ipe.LayoutCollection} options.collection
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
     * Renders the selection menu for picking Layouts.
     *
     * @return {Drupal.panels_ipe.LayoutPicker}
     *   Return this, for chaining.
     */
    render: function () {
      var current_layout = Drupal.panels_ipe.app.get('layout').get('id');
      // If we don't have layouts yet, pull some from the server.
      if (!this.collection) {
        // Indicate an AJAX request.
        this.$el.html(this.template_loading());

        // Fetch a list of layouts from the server.
        this.collection = new Drupal.panels_ipe.LayoutCollection();
        var self = this;
        this.collection.fetch().done(function () {
          // We have a collection now, re-render ourselves.
          self.render();
        });

        return this;
      }

      // Render our categories.
      this.renderCategories();

      // Flag the current layout.
      var current_layout_text = '<p>' + Drupal.t('Current Layout') + '</p>';
      this.$('[data-layout-id="' + current_layout + '"]').append(current_layout_text);

      // Prepend the current layout as its own category.
      this.$('.ipe-categories').prepend(this.template_category({
        name: Drupal.t('Current Layout'),
        count: null,
        active: this.activeCategory === 'Current Layout'
      }));

      // If we're viewing the current layout tab, show a custom item.
      if (this.activeCategory && this.activeCategory === 'Current Layout') {
        // Hide the search box.
        this.$('.ipe-category-picker-search').hide();

        this.collection.each(function (layout) {
          if (Drupal.panels_ipe.app.get('layout').get('id') === layout.get('id')) {
            this.$('.ipe-category-picker-top').append(this.template_item(layout));
          }
        }, this);
      }

      return this;
    },

    /**
     * Callback for our CategoryView, which renders an individual item.
     *
     * @param {Drupal.panels_ipe.LayoutModel} layout
     *   The Layout that needs rendering.
     *
     * @return {string}
     *   The rendered block plugin.
     */
    template_item: function (layout) {
      return this.template_layout(layout.toJSON());
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
      // Get the current layout_id.
      var layout_id = $(e.currentTarget).data('layout-id');

      var layout = this.collection.get(layout_id);
      var url = Drupal.panels_ipe.urlRoot(drupalSettings) + '/layouts/' + layout_id + '/form';

      return {url: url, model: layout};
    }

  });

}(jQuery, _, Backbone, Drupal));
