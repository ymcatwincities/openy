/**
 * @file
 * Sorts a collection into categories and renders them as tabs with content.
 *
 * see Drupal.panels_ipe.CategoryView
 *
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.CategoryView = Backbone.View.extend(/** @lends Drupal.panels_ipe.CategoryView# */{

    /**
     * The name of the currently selected category.
     *
     * @type {string}
     */
    activeCategory: null,

    /**
     * @type {Backbone.Collection}
     */
    collection: null,

    /**
     * @type {function}
     */
    template: _.template(
      '<div class="ipe-category-picker-top"></div><div class="ipe-category-picker-bottom"><div class="ipe-categories"></div></div>'
    ),

    /**
     * @type {function}
     */
    template_category: _.template(
      '<a class="ipe-category<% if (active) { %> active<% } %>" data-category="<%- name %>">' +
      '  <%- name %>' +
      '  <% if (count) { %><div class="ipe-category-count"><%- count %></div><% } %>' +
      '</a>'
    ),

    /**
     * @type {function}
     *
     * A function to render an item, provided by whoever uses this View.
     */
    template_item: null,

    /**
     * @type {function}
     *
     * A function to display the form wrapper.
     */
    template_form: null,

    /**
     * @type {object}
     */
    events: {
      'click [data-category]': 'toggleCategory'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {Object} options
     *   An object containing the following keys:
     * @param {Backbone.Collection} options.collection
     *   An optional initial collection.
     */
    initialize: function (options) {
      if (options && options.collection) {
        this.collection = options.collection;
      }
    },

    /**
     * Renders the selection menu for picking categories.
     *
     * @return {Drupal.panels_ipe.CategoryView}
     *   Return this, for chaining.
     */
    renderCategories: function () {
      // Empty ourselves.
      this.$el.html(this.template());

      // Get a list of categories from the collection.
      var categories_count = {};
      this.collection.each(function (model) {
        var category = model.get('category');
        if (!categories_count[category]) {
          categories_count[category] = 0;
        }
        ++categories_count[category];
      });

      // Render each category.
      for (var i in categories_count) {
        if (categories_count.hasOwnProperty(i)) {
          this.$('.ipe-categories').append(this.template_category({
            name: i,
            count: categories_count[i],
            active: this.activeCategory === i
          }));
        }
      }

      // Check if a category is selected. If so, render the top-tray.
      if (this.activeCategory) {
        var $top = this.$('.ipe-category-picker-top');
        $top.addClass('active');
        this.collection.each(function (model) {
          if (model.get('category') === this.activeCategory) {
            $top.append(this.template_item(model));
          }
        }, this);
      }

      return this;
    },

    /**
     * Reacts to a category being clicked.
     *
     * @param {Object} e
     *   The event object.
     */
    toggleCategory: function (e) {
      var category = $(e.currentTarget).data('category');

      var animation = false;

      // No category is open.
      if (!this.activeCategory) {
        this.activeCategory = category;
        animation = 'slideDown';
      }
      // The same category is clicked twice.
      else if (this.activeCategory === category) {
        this.activeCategory = null;
        animation = 'slideUp';
      }
      // Another category is already open.
      else if (this.activeCategory) {
        this.activeCategory = category;
      }

      // Trigger a re-render, with animation if needed.
      if (animation === 'slideUp') {
        // Close the tab, then re-render.
        var self = this;
        this.$('.ipe-category-picker-top')[animation]('fast', function () { self.render(); });
      }
      else if (animation === 'slideDown') {
        // We need to render first as hypothetically nothing is open.
        this.render();
        this.$('.ipe-category-picker-top').hide();
        this.$('.ipe-category-picker-top')[animation]('fast');
      }
      else {
        this.render();
      }
    },

    /**
     * Informs us of our form's callback URL.
     *
     * @param {Object} e
     *   The event object.
     *
     * @return {Object}
     *   An object containing the properties "url" and "model".
     */
    getFormInfo: function(e) {},

    /**
     * Displays a Configuration form in our top region.
     *
     * @param {Object} e
     *   The event object.
     */
    displayForm: function (e) {
      var self = this;

      var info = this.getFormInfo(e);

      // Indicate an AJAX request.
      this.$('.ipe-category-picker-top').fadeOut('fast', function () {
        self.$('.ipe-category-picker-top').html(self.template_form(info.model.toJSON()));
        self.$('.ipe-category-picker-top').fadeIn('fast');

        // Setup the Drupal.Ajax instance.
        var ajax = Drupal.ajax({
          url: info.url,
          submit: { js: true }
        });

        // Remove our throbber on load.
        ajax.options.complete = function () {
          self.$('.ipe-category-picker-top .ipe-icon-loading').remove();

          self.setFormMaxHeight();

          self.$('.ipe-category-picker-top *').hide().fadeIn();
        };

        // Make the Drupal AJAX request.
        ajax.execute();
      });
    },

    /**
     * Calculates and sets maximum height of our form based on known floating
     * and fixed elements.
     */
    setFormMaxHeight: function() {
      // Calculate the combined height of (known) floating elements.
      var used_height = $('#toolbar-item-administration-tray:visible').outerHeight() +
      $('#toolbar-bar').outerHeight() +
      this.$('.ipe-category-picker-bottom').outerHeight();

      // 175 (px) is an arbitrary offset, just to give padding on top.
      var max_height = $(window).height() - used_height - 175;

      // Set the form's max height.
      this.$('.ipe-form').css('max-height', max_height);
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
