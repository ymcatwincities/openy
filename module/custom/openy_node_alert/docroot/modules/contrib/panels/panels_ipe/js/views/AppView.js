/**
 * @file
 * The primary Backbone view for Panels IPE. For now this only controls the
 * bottom tray, but in the future could have a larger scope.
 *
 * see Drupal.panels_ipe.AppModel
 */

(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.AppView = Backbone.View.extend(/** @lends Drupal.panels_ipe.AppView# */{

    /**
     * @type {function}
     */
    template: _.template('<div class="ipe-tab-wrapper"></div>'),

    /**
     * @type {function}
     */
    template_content_block_edit: _.template(
      '<h4>' + Drupal.t('Edit existing "<strong><%- label %></strong>" content') + '</h4>' +
      '<div class="ipe-block-form ipe-form"><div class="ipe-icon ipe-icon-loading"></div></div>'
    ),

    /**
     * @type {Drupal.panels_ipe.TabsView}
     */
    tabsView: null,

    /**
     * @type {Drupal.panels_ipe.LayoutView}
     */
    layoutView: null,

    /**
     * @type {Drupal.panels_ipe.AppModel}
     */
    model: null,

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.AppModel} options.model
     *   The application state model.
     * @param {Object} options.tabContentViews
     *   An object mapping TabModel ids to arbitrary Backbone views.
     */
    initialize: function (options) {
      this.model = options.model;

      // Create a TabsView instance.
      this.tabsView = new Drupal.panels_ipe.TabsView({
        collection: this.model.get('tabCollection'),
        tabViews: options.tabContentViews
      });

      // Display the cancel and save tab based on whether or not we have unsaved changes.
      this.model.get('cancelTab').set('hidden', !this.model.get('unsaved'));
      this.model.get('saveTab').set('hidden', !this.model.get('unsaved'));
      // Do not show the edit tab if the IPE is locked.
      this.model.get('editTab').set('hidden', this.model.get('locked'));
      this.model.get('lockedTab').set('hidden', !this.model.get('locked'));

      // Listen to important global events throughout the app.
      this.listenTo(this.model, 'changeLayout', this.changeLayout);
      this.listenTo(this.model, 'addBlockPlugin', this.addBlockPlugin);
      this.listenTo(this.model, 'configureBlock', this.configureBlock);
      this.listenTo(this.model, 'addContentBlock', this.addContentBlock);
      this.listenTo(this.model, 'editContentBlock', this.editContentBlock);
      this.listenTo(this.model, 'editContentBlockDone', this.editContentBlockDone);

      // Listen to tabs that don't have associated BackboneViews.
      this.listenTo(this.model.get('editTab'), 'change:active', this.clickEditTab);
      this.listenTo(this.model.get('saveTab'), 'change:active', this.clickSaveTab);
      this.listenTo(this.model.get('cancelTab'), 'change:active', this.clickCancelTab);
      this.listenTo(this.model.get('lockedTab'), 'change:active', this.clickLockedTab);

      // Change the look/feel of the App if we have unsaved changes.
      this.listenTo(this.model, 'change:unsaved', this.unsavedChange);
    },

    /**
     * Appends the IPE tray to the bottom of the screen.
     *
     * @param {bool} render_layout
     *   Whether or not the layout should be rendered. Useful for just calling
     *   render on UI elements and not content.
     *
     * @return {Drupal.panels_ipe.AppView}
     *   Returns this, for chaining.
     */
    render: function (render_layout) {
      render_layout = typeof render_layout !== 'undefined' ? render_layout : true;

      // Empty our list.
      this.$el.html(this.template(this.model.toJSON()));
      // Add our tab collection to the App.
      this.tabsView.setElement(this.$('.ipe-tab-wrapper')).render();

      // If we have unsaved changes, add a special class.
      this.$el.toggleClass('unsaved', this.model.get('unsaved'));

      // Re-render our layout.
      if (this.layoutView && render_layout) {
        this.layoutView.render();
      }
      return this;
    },

    /**
     * Actives all regions and blocks for editing.
     */
    openIPE: function () {
      var active = this.model.get('active');
      if (active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({active: true});

      // Set the layout's active state correctly.
      this.model.get('layout').set({active: true});

      this.$el.addClass('active');

      // Add a top-level body class.
      $('body').addClass('panels-ipe-active');
    },

    /**
     * Deactivate all regions and blocks for editing.
     */
    closeIPE: function () {
      var active = this.model.get('active');
      if (!active) {
        return;
      }

      // Set our active state correctly.
      this.model.set({active: false});

      // Set the layout's active state correctly.
      this.model.get('layout').set({active: false});

      this.$el.removeClass('active');

      // Remove our top-level body class.
      $('body').removeClass('panels-ipe-active');
    },

    /**
     * Event callback for when a new layout has been selected.
     *
     * @param {Drupal.panels_ipe.LayoutModel} layout
     *   The new layout model.
     */
    changeLayout: function (layout) {
      // Early render the tabs and layout - if changing the Layout was the first
      // action on the page the Layout would have never been rendered.
      this.render();

      // Grab all the blocks from the current layout.
      var regions = this.model.get('layout').get('regionCollection');
      var block_collection = new Drupal.panels_ipe.BlockCollection();

      // @todo Our backend should inform us of region suggestions.
      regions.each(function (region) {
        // If a layout with the same name exists, copy our block collection.
        var new_region = layout.get('regionCollection').get(region.get('name'));
        if (new_region) {
          new_region.set('blockCollection', region.get('blockCollection'));
        }
        // Otherwise add these blocks to our generic pool.
        else {
          block_collection.add(region.get('blockCollection').toJSON());
        }
      });

      // Get the first region in the layout.
      var first_region = layout.get('regionCollection').at(0);

      // Merge our block collection with the existing block collection.
      block_collection.each(function (block) {
        first_region.get('blockCollection').add(block);
      });

      // Change the default layout in our AppModel.
      this.model.set({layout: layout});

      // Change the LayoutView's layout.
      this.layoutView.changeLayout(layout);

      // Re-render the app.
      this.render();

      // Indicate that there are unsaved changes in the app.
      this.model.set('unsaved', true);

      // Switch back to the edit tab.
      this.tabsView.switchTab('edit');
    },

    /**
     * Sets the IPE active state based on the "Edit" TabModel.
     */
    clickEditTab: function () {
      var active = this.model.get('editTab').get('active');
      if (active) {
        this.openIPE();
      }
      else {
        this.closeIPE();
      }
    },

    /**
     * Cancels another user's temporary changes and refreshes the page.
     */
    clickLockedTab: function () {
      var locked_tab = this.model.get('lockedTab');

      if (confirm(Drupal.t('This page is being edited by another user, and is locked from editing by others. Would you like to break this lock?'))) {
        if (locked_tab.get('active') && !locked_tab.get('loading')) {
          // Remove our changes and refresh the page.
          locked_tab.set({loading: true});
          $.ajax(Drupal.panels_ipe.urlRoot(drupalSettings) + '/cancel')
            .done(function () {
              location.reload();
            });
        }
      }
      else {
        locked_tab.set('active', false, {silent: true});
      }
    },

    /**
     * Saves our layout to the server.
     */
    clickSaveTab: function () {
      if (this.model.get('saveTab').get('active')) {
        // Save the Layout and disable the tab.
        var self = this;
        self.model.get('saveTab').set({loading: true});
        this.model.get('layout').save().done(function () {
          self.model.get('saveTab').set({loading: false, active: false});
          self.model.set('unsaved', false);
          self.tabsView.render();
        });
      }
    },

    /**
     * Cancels our temporary changes and refreshes the page.
     */
    clickCancelTab: function () {
      var cancel_tab = this.model.get('cancelTab');

      if (confirm(Drupal.t('Are you sure you want to cancel your changes?'))) {
        if (cancel_tab.get('active') && !cancel_tab.get('loading')) {
          // Remove our changes and refresh the page.
          cancel_tab.set({loading: true});
          $.ajax(Drupal.panels_ipe.urlRoot(drupalSettings) + '/cancel')
            .done(function (data) {
              location.reload();
            });
        }
      }
      else {
        cancel_tab.set('active', false, {silent: true});
      }
    },

    /**
     * Adds a new BlockPlugin to the screen.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The new BlockModel
     * @param {string} region
     *   The region the block should be placed in.
     */
    addBlockPlugin: function (block, region) {
      this.layoutView.addBlock(block, region);

      // Indicate that there are unsaved changes in the app.
      this.model.set('unsaved', true);

      // Switch back to the edit tab.
      this.tabsView.switchTab('edit');
    },

    /**
     * Opens the Manage Content tray when configuring an existing Block.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The Block that needs to have its form opened.
     */
    configureBlock: function (block) {
      var info = {
        url: Drupal.panels_ipe.urlRoot(drupalSettings) + '/block_plugins/' + block.get('id') + '/block/' + block.get('uuid') + '/form',
        model: block
      };

      this.loadBlockForm(info);
    },

    /**
     * Opens the Manage Content tray after adding a new Block Content entity.
     *
     * @param {string} uuid
     *   The UUID of the newly added Content Block.
     */
    addContentBlock: function (uuid) {
      // Delete the current block plugin collection so that a new one is pulled in.
      delete this.tabsView.tabViews['manage_content'].collection;

      // Auto-click the new block, which we know is in the "Custom" category.
      // @todo When configurable categories are in, determine this from the
      // passed-in settings.
      this.tabsView.tabViews['manage_content'].autoClick = '[data-plugin-id="block_content:' + uuid + '"]';
      this.tabsView.tabViews['manage_content'].activeCategory = 'Custom';

      this.tabsView.tabViews['manage_content'].render();
    },

    /**
     * Opens the Manage Content tray when editing an existing Content Block.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The Block that needs to have its form opened.
     */
    editContentBlock: function (block) {
      var plugin_split = block.get('id').split(':');

      var info = {
        url: Drupal.panels_ipe.urlRoot(drupalSettings) + '/block_content/edit/block/' + plugin_split[1] + '/form',
        model: block
      };

      this.loadBlockForm(info, this.template_content_block_edit);
    },

    /**
     * React after a content block has been edited.
     *
     * @param {string} block_content_uuid
     *   The UUID of the Block Content entity that was edited.
     */
    editContentBlockDone: function (block_content_uuid) {
      // Find all on-screen Blocks that render this Content Block and refresh
      // them from the server.
      this.layoutView.model.get('regionCollection').each(function (region) {
        var id = 'block_content:' + block_content_uuid;
        var blocks = region.get('blockCollection').where({id: id});

        for (var i in blocks) {
          if (blocks.hasOwnProperty(i)) {
            blocks[i].set('syncing', true);
            blocks[i].fetch();
          }
        }

      });

      this.tabsView.switchTab('edit');
    },

    /**
     * Hides/shows certain elements if our unsaved state changes.
     */
    unsavedChange: function () {
      // Show/hide the cancel tab based on our saved status.
      this.model.get('cancelTab').set('hidden', !this.model.get('unsaved'));
      this.model.get('saveTab').set('hidden', !this.model.get('unsaved'));

      // Re-render ourselves, pass "false" as we don't need to re-render the
      // layout, just the tabs.
      this.render(false);
    },

    /**
     * Helper function to switch tabs to Manage Content and load an arbitrary
     * form.
     *
     * @param {object} info
     *   An object compatible with Drupal.panels_ipe.CategoryView.loadForm()
     * @param {function} template
     *   An optional callback function for the form template.
     */
    loadBlockForm: function (info, template) {
      // We're going to open the manage content tab, which may take time to
      // render. Load the Block edit form on render.
      var manage_content = this.tabsView.tabViews['manage_content'];
      manage_content.on('render', function () {

        if (template) {
          manage_content.loadForm(info, template);
        }
        else {
          manage_content.loadForm(info);
        }

        // We only need this event to trigger once.
        manage_content.off('render', null, this);
      }, this);

      // Disable the active category to avoid confusion.
      manage_content.activeCategory = null;

      this.tabsView.switchTab('manage_content');
    }

  });

}(jQuery, _, Backbone, Drupal, drupalSettings));
