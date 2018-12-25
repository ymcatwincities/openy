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
     * The attribute that we should search for. Defaults to "label".
     *
     * @type {string}
     */
    searchAttribute: 'label',

    /**
     * @type {function}
     */
    template: _.template(
      '<div class="ipe-category-picker-search">' +
      '  <span class="ipe-icon ipe-icon-search"></span>' +
      '  <input type="text" placeholder="<%= search_prompt %>" />' +
      '  <input type="submit" value="' + Drupal.t('Search') + '" />' +
      '</div>' +
      '<div class="ipe-category-picker-top"></div>' +
      '<div class="ipe-category-picker-bottom" tabindex="-1">' +
      '  <div class="ipe-categories"></div>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_category: _.template(
      '<a href="javascript:;" class="ipe-category<% if (active) { %> active<% } %>" data-category="<%- name %>">' +
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
      'click [data-category]': 'toggleCategory',
      'keyup .ipe-category-picker-search input[type="text"]': 'searchCategories'
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

      this.on('tabActiveChange', this.tabActiveChange, this);
    },

    /**
     * Renders the selection menu for picking categories.
     *
     * @return {Drupal.panels_ipe.CategoryView}
     *   Return this, for chaining.
     */
    renderCategories: function () {
      // Empty ourselves.
      var search_prompt;
      if (this.activeCategory) {
        search_prompt = Drupal.t('Search current category');
      }
      else {
        search_prompt = Drupal.t('Search all categories');
      }
      this.$el.html(this.template({search_prompt: search_prompt}));

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
        this.$('.ipe-category-picker-bottom').addClass('top-open');
        this.collection.each(function (model) {
          if (model.get('category') === this.activeCategory) {
            $top.append(this.template_item(model));
          }
        }, this);

        // Add a top-level body class.
        $('body').addClass('panels-ipe-category-picker-top-open');

        // Focus on the active category.
        this.$('.ipe-category.active').focus();
      }
      else {
        // Remove our top-level body class.
        $('body').removeClass('panels-ipe-category-picker-top-open');

        // Focus on the bottom region.
        this.$('.ipe-category-picker-bottom').focus();
      }

      this.setTopMaxHeight();

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
        this.$('.ipe-category-picker-top')[animation]('fast', function () {
          self.render();
        });
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

      // Focus on the first focusable element.
      this.$('.ipe-category-picker-top :focusable:first').focus();
    },

    /**
     * Informs us of our form's callback URL.
     *
     * @param {Object} e
     *   The event object.
     */
    getFormInfo: function (e) {},

    /**
     * Determines form info from the current click event and displays a form.
     *
     * @param {Object} e
     *   The event object.
     */
    displayForm: function (e) {
      var info = this.getFormInfo(e);

      // Indicate an AJAX request.
      this.loadForm(info);
    },

    /**
     * Reacts to the search field changing and displays category items based on
     * our search.
     *
     * @param {Object} e
     *   The event object.
     */
    searchCategories: function (e) {
      // Grab the formatted search from out input field.
      var search = $(e.currentTarget).val().trim().toLowerCase();

      // If the search is empty, re-render the view.
      if (search.length === 0) {
        this.render();
        this.$('.ipe-category-picker-search input').focus();
        return;
      }

      // Filter our collection based on the input.
      var results = this.collection.filter(function (model) {
        var attribute = model.get(this.searchAttribute);
        return attribute.toLowerCase().indexOf(search) !== -1;
      }, this);

      // Empty ourselves.
      var $top = this.$('.ipe-category-picker-top');
      $top.empty();

      // Render categories that matched the search.
      if (results.length > 0) {
        $top.addClass('active');
        this.$('.ipe-category-picker-bottom').addClass('top-open');

        for (var i in results) {
          // If a category is empty, search within that category.
          if (this.activeCategory) {
            if (results[i].get('category') === this.activeCategory) {
              $top.append(this.template_item(results[i]));
            }
          }
          else {
            $top.append(this.template_item(results[i]));
          }
        }

        $('body').addClass('panels-ipe-category-picker-top-open');
      }
      else {
        $top.removeClass('active');
        $('body').removeClass('panels-ipe-category-picker-top-open');
      }

      this.setTopMaxHeight();
    },

    /**
     * Displays a configuration form in our top region.
     *
     * @param {Object} info
     *   An object containing the form URL the model for our form template.
     * @param {function} template
     *   An optional callback function for the form template.
     */
    loadForm: function (info, template) {
      template = template || this.template_form;
      var self = this;

      // Hide the search box.
      this.$('.ipe-category-picker-search').fadeOut('fast');

      this.$('.ipe-category-picker-top').fadeOut('fast', function () {
        self.$('.ipe-category-picker-top').html(template(info.model.toJSON()));
        self.$('.ipe-category-picker-top').fadeIn('fast');

        // Setup the Drupal.Ajax instance.
        var ajax = Drupal.ajax({
          url: info.url,
          submit: {js: true}
        });

        // Remove our throbber on load.
        ajax.options.complete = function () {
          self.$('.ipe-category-picker-top .ipe-icon-loading').remove();

          self.setTopMaxHeight();

          // Remove the inline display style and add a unique class.
          self.$('.ipe-category-picker-top').css('display', '').addClass('form-displayed');

          self.$('.ipe-category-picker-top').hide().fadeIn();
          self.$('.ipe-category-picker-bottom').addClass('top-open');

          // Focus on the first focusable element.
          self.$('.ipe-category-picker-top :focusable:first').focus();
        };

        // Make the Drupal AJAX request.
        ajax.execute();
      });
    },

    /**
     * Responds to our associated tab being opened/closed.
     *
     * @param {bool} state
     *   Whether or not our associated tab is open.
     */
    tabActiveChange: function (state) {
      $('body').toggleClass('panels-ipe-category-picker-top-open', state);
    },

    /**
     * Calculates and sets maximum height of our top area based on known
     * floating and fixed elements.
     */
    setTopMaxHeight: function () {
      // Calculate the combined height of (known) floating elements.
      var used_height = this.$('.ipe-category-picker-bottom').outerHeight() +
        this.$('.ipe-category-picker-search').outerHeight() +
        $('.ipe-tabs').outerHeight();

      // Add optional toolbar support.
      var toolbar = $('#toolbar-bar');
      if (toolbar.length > 0) {
        used_height += $('#toolbar-item-administration-tray:visible').outerHeight() +
        toolbar.outerHeight();
      }

      // The .ipe-category-picker-top padding is 30 pixels, plus five for margin.
      var max_height = $(window).height() - used_height - 35;

      // Set the form's max height.
      this.$('.ipe-category-picker-top').css('max-height', max_height);
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
