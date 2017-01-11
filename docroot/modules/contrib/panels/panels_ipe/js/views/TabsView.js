/**
 * @file
 * The primary Backbone view for a tab collection.
 *
 * see Drupal.panels_ipe.TabCollection
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.TabsView = Backbone.View.extend(/** @lends Drupal.panels_ipe.TabsView# */{

    /**
     * @type {function}
     */
    template_tab: _.template(
      '<li class="ipe-tab<% if (active) { %> active<% } %>" data-tab-id="<%- id %>">' +
      '  <a href="javascript:;" title="<%- title %>">' +
      '    <span class="ipe-icon ipe-icon-<% if (loading) { %>loading<% } else { print(id) } %>"></span>' +
      '    <span class="ipe-tab-title"><%- title %></span>' +
      '  </a>' +
      '</li>'
    ),

    /**
     * @type {function}
     */
    template_content: _.template('<div class="ipe-tab-content<% if (active) { %> active<% } %>" data-tab-content-id="<%- id %>"></div>'),

    /**
     * @type {object}
     */
    events: {
      'click .ipe-tab > a': 'switchTab'
    },

    /**
     * @type {Drupal.panels_ipe.TabCollection}
     */
    collection: null,

    /**
     * @type {Object}
     *
     * An object mapping tab IDs to Backbone views.
     */
    tabViews: {},

    /**
     * @constructs
     *
     * @augments Backbone.TabsView
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {object} options.tabViews
     *   An object mapping tab IDs to Backbone views.
     */
    initialize: function (options) {
      this.tabViews = options.tabViews;

      // Bind our global key down handler to the document.
      $(document).bind('keydown', $.proxy(this.keydownHandler, this));
    },

    /**
     * Renders our tab collection.
     *
     * @return {Drupal.panels_ipe.TabsView}
     *   Return this, for chaining.
     */
    render: function () {
      // Empty our list.
      this.$el.empty();

      // Setup the initial wrapping elements.
      this.$el.append('<ul class="ipe-tabs"></ul>');
      this.$el.append('<div class="ipe-tabs-content" tabindex="-1"></div>');

      // Remove any previously added body classes.
      $('body').removeClass('panels-ipe-tabs-open');

      // Append each of our tabs and their tab content view.
      this.collection.each(function (tab) {
        // Return early if this tab is hidden.
        if (tab.get('hidden')) {
          return;
        }

        // Append the tab.
        var id = tab.get('id');

        this.$('.ipe-tabs').append(this.template_tab(tab.toJSON()));

        // Check to see if this tab has content.
        if (tab.get('active') && this.tabViews[id]) {
          // Add a top-level body class.
          $('body').addClass('panels-ipe-tabs-open');

          // Render the tab content.
          this.$('.ipe-tabs-content').append(this.template_content(tab.toJSON()));
          this.tabViews[id].setElement('[data-tab-content-id="' + id + '"]').render();
        }
      }, this);

      // Focus on the current tab.
      this.$('.ipe-tab.active a').focus();

      return this;
    },

    /**
     * Switches the current tab.
     *
     * @param {Object} e
     *   The event object.
     */
    switchTab: function (e) {
      var id;
      if (typeof e === 'string') {
        id = e;
      }
      else {
        e.preventDefault();
        id = $(e.currentTarget).parent().data('tab-id');
      }

      // Disable all existing tabs.
      var animation = null;
      var already_open = false;
      this.collection.each(function (tab) {
        // If the tab is loading, do nothing.
        if (tab.get('loading')) {
          return;
        }

        // Don't repeat comparisons, if possible.
        var clicked = tab.get('id') === id;
        var active = tab.get('active');

        // If the user is clicking the same tab twice, close it.
        if (clicked && active) {
          tab.set('active', false);
          animation = 'close';
        }
        // If this is the first click, open the tab.
        else if (clicked) {
          tab.set('active', true);
          // Only animate the tab if there is an associate Backbone View.
          if (this.tabViews[id]) {
            animation = 'open';
          }
        }
        // The tab wasn't clicked, make sure it's closed.
        else {
          // Mark that the View was already open.
          if (active) {
            already_open = true;
          }
          tab.set('active', false);
        }

        // Inform the tab's view of the change.
        if (this.tabViews[tab.get('id')]) {
          this.tabViews[tab.get('id')].trigger('tabActiveChange', tab.get('active'));
        }
      }, this);

      // Trigger a re-render, with animation if needed.
      if (animation === 'close') {
        this.closeTabContent();
      }
      else if (animation === 'open' && !already_open) {
        this.openTabContent();
      }
      else {
        this.render();
      }
    },

    /**
     * Handles keypress events, checking for contextual commands in IPE.
     *
     * @param {Object} e
     *   The event object.
     */
    keydownHandler: function (e) {
      if (e.keyCode === 27) {
        // Get the currently focused element.
        var $focused = $(':focus');

        // If a tab is currently open and we are in focus, close the tab.
        if (this.$el.has($focused).length) {
          var active_tab = false;
          this.collection.each(function (tab) {
            if (tab.get('active')) {
              active_tab = tab.get('id');
            }
          });
          if (active_tab) {
            this.switchTab(active_tab);
          }
        }
      }
    },

    /**
     * Closes any currently open tab.
     */
    closeTabContent: function () {
      // Close the tab, then re-render.
      var self = this;
      this.$('.ipe-tabs-content')['slideUp']('fast', function () {
        self.render();
      });

      // Remove our top-level body class.
      $('body').removeClass('panels-ipe-tabs-open');
    },

    /**
     * Opens any currently closed tab.
     */
    openTabContent: function () {
      // We need to render first as hypothetically nothing is open.
      this.render();
      this.$('.ipe-tabs-content').hide();
      this.$('.ipe-tabs-content')['slideDown']('fast');
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
