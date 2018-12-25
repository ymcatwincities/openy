/**
 * @file
 * The primary Backbone view for a Layout.
 *
 * see Drupal.panels_ipe.LayoutModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutView = Backbone.View.extend(/** @lends Drupal.panels_ipe.LayoutView# */{

    /**
     * @type {function}
     */
    template_region_actions: _.template(
      '<div class="ipe-actions" data-region-action-id="<%- name %>">' +
      '  <h5>' + Drupal.t('Region: <%- name %>') + '</h5>' +
      '  <ul class="ipe-action-list"></ul>' +
      '</div>'
    ),

    /**
     * @type {function}
     */
    template_region_option: _.template(
      '<option data-region-option-name="<%- name %>"><%- name %></option>'
    ),

    /**
     * @type {function}
     */
    template_region_droppable: _.template(
      '<div class="ipe-droppable" data-droppable-region-name="<%- region %>" data-droppable-index="<%- index %>"></div>'
    ),

    /**
     * @type {Drupal.panels_ipe.LayoutModel}
     */
    model: null,

    /**
     * @type {Array}
     *   An array of child Drupal.panels_ipe.BlockView objects.
     */
    blockViews: [],

    /**
     * @type {object}
     */
    events: {
      'mousedown [data-action-id="move"] > select': 'showBlockRegionList',
      'blur [data-action-id="move"] > select': 'hideBlockRegionList',
      'change [data-action-id="move"] > select': 'newRegionSelected',
      'click [data-action-id="up"]': 'moveBlock',
      'click [data-action-id="down"]': 'moveBlock',
      'click [data-action-id="remove"]': 'removeBlock',
      'click [data-action-id="configure"]': 'configureBlock',
      'click [data-action-id="edit-content-block"]': 'editContentBlock',
      'drop .ipe-droppable': 'dropBlock'
    },

    /**
     * @type {object}
     */
    droppable_settings: {
      tolerance: 'pointer',
      hoverClass: 'hover',
      accept: '[data-block-id]'
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.LayoutModel} options.model
     *   The layout state model.
     */
    initialize: function (options) {
      this.model = options.model;
      // Initialize our html, this never changes.
      if (this.model.get('html')) {
        this.$el.html(this.model.get('html'));
      }

      this.on('tabActiveChange', this.tabActiveChange, this);
      this.listenTo(this.model, 'change:active', this.changeState);
    },

    /**
     * Re-renders our blocks, we have no HTML to be re-rendered.
     *
     * @return {Drupal.panels_ipe.LayoutView}
     *   Returns this, for chaining.
     */
    render: function () {
      // Remove all existing BlockViews.
      for (var i in this.blockViews) {
        if (this.blockViews.hasOwnProperty(i)) {
          this.blockViews[i].remove();
        }
      }
      this.blockViews = [];

      // Remove any active-state items that may remain rendered.
      this.$('.ipe-actions').remove();
      this.$('.ipe-droppable').remove();

      // Re-attach all BlockViews to appropriate regions.
      this.model.get('regionCollection').each(function (region) {
        var region_selector = '[data-region-name="' + region.get('name') + '"]';

        // Add an initial droppable area to our region if this is the first render.
        if (this.model.get('active')) {
          this.$(region_selector).prepend($(this.template_region_droppable({
            region: region.get('name'),
            index: 0
          })).droppable(this.droppable_settings));

          // Prepend the action header for this region.
          this.$(region_selector).prepend(this.template_region_actions(region.toJSON()));
        }

        var i = 1;
        region.get('blockCollection').each(function (block) {
          var block_selector = '[data-block-id="' + block.get('uuid') + '"]';

          // Attach an empty element for our View to attach itself to.
          if (this.$(block_selector).length === 0) {
            var empty_elem = $('<div data-block-id="' + block.get('uuid') + '">');
            this.$(region_selector).append(empty_elem);
          }

          // Attach a View to this empty element.
          var block_view = new Drupal.panels_ipe.BlockView({
            model: block,
            el: block_selector
          });
          this.blockViews.push(block_view);

          // Render the new BlockView.
          block_view.render();

          // Prepend/append droppable regions if the Block is active.
          if (this.model.get('active')) {
            block_view.$el.after($(this.template_region_droppable({
              region: region.get('name'),
              index: i
            })).droppable(this.droppable_settings));
          }

          ++i;
        }, this);
      }, this);

      // Attach any Drupal behaviors.
      Drupal.attachBehaviors(this.el);

      return this;
    },

    /**
     * Prepends Regions and Blocks with action items.
     *
     * @param {Drupal.panels_ipe.LayoutModel} model
     *   The target LayoutModel.
     * @param {bool} value
     *   The desired active state.
     * @param {Object} options
     *   Unused options.
     */
    changeState: function (model, value, options) {
      // Sets the active state of child blocks when our state changes.
      this.model.get('regionCollection').each(function (region) {
        // BlockViews handle their own rendering, so just set the active value here.
        region.get('blockCollection').each(function (block) {
          block.set({active: value});
        }, this);
      }, this);

      // Re-render ourselves.
      this.render();
    },

    /**
     * Replaces the "Move" button with a select list of regions.
     *
     * @param {Object} e
     *   The event object.
     */
    showBlockRegionList: function (e) {
      // Get the BlockModel id (uuid).
      var id = this.getEventBlockUuid(e);

      $(e.currentTarget).empty();

      // Add other regions to select list.
      this.model.get('regionCollection').each(function (region) {
        var option = $(this.template_region_option(region.toJSON()));
        // If this is the current region, place it first in the list.
        if (region.getBlock(id)) {
          option.attr('selected', 'selected');
          $(e.currentTarget).prepend(option);
        }
        else {
          $(e.currentTarget).append(option);
        }
      }, this);
    },

    /**
     * Hides the region selector.
     *
     * @param {Object} e
     *   The event object.
     */
    hideBlockRegionList: function (e) {
      $(e.currentTarget).html('<option>' + Drupal.t('Move') + '</option>');
    },

    /**
     * React to a new region being selected.
     *
     * @param {Object} e
     *   The event object.
     */
    newRegionSelected: function (e) {
      var block_uuid = this.getEventBlockUuid(e);
      var new_region_name = $(e.currentTarget).children(':selected').data('region-option-name');

      if (new_region_name) {
        this.moveBlockToRegion(block_uuid, new_region_name);
        this.hideBlockRegionList(e);
        this.render();
        this.highlightBlock(block_uuid, true);
        this.saveToTempStore();
      }
    },

    /**
     * Get the block Uuid related to an event.
     *
     * @param {Object} e
     *   The event object.
     *
     * @return {String}
     *   The block Uuid
     */
    getEventBlockUuid: function (e) {
      return $(e.currentTarget).closest('[data-block-action-id]').data('block-action-id');
    },

    /**
     * Get the block Uuid related to an event.
     *
     * @param {Object} e
     *   The event object.
     *
     * @return {String}
     *   The block Uuid
     */
    getEventBlockId: function (e) {
      return $(e.currentTarget).closest('[data-block-edit-id]').data('block-edit-id');
    },

    /**
     * Moves an existing Block to a new region.
     *
     * @param {string} block_uuid
     *   The universally unique identifier of the block.
     * @param {string} target_region_id
     *   The id of the target region.
     */
    moveBlockToRegion: function (block_uuid, target_region_id) {
      var target_region = this.model.get('regionCollection').get(target_region_id);
      var original_region = this.getRegionContainingBlock(block_uuid);
      target_region.addBlock(original_region.getBlock(block_uuid));
      original_region.removeBlock(block_uuid);
    },

    /**
     * Determines what region a Block resides in.
     *
     * @param {string} block_uuid
     *   The universally unique identifier of the block.
     *
     * @return {Drupal.panels_ipe.RegionModel|undefined}
     *   The region containing the block if it was found.
     */
    getRegionContainingBlock: function (block_uuid) {
      var region_collection = this.model.get('regionCollection');
      for (var i = 0, l = region_collection.length; i < l; i++) {
        var region = region_collection.at(i);
        if (region.hasBlock(block_uuid)) {
          return region;
        }
      }
    },

    /**
     * Highlights a block by adding a css class and optionally scrolls to the
     * block's location.
     *
     * @param {string} block_uuid
     *   The universally unique identifier of the block.
     * @param {bool} scroll
     *   Whether or not the page should scroll to the block. Defaults to false.
     */
    highlightBlock: function (block_uuid, scroll) {
      scroll = scroll || false;

      var $block = this.$('[data-block-id="' + block_uuid + '"]');
      $block.addClass('ipe-highlight');

      if (scroll) {
        $('body').animate({scrollTop: $block.offset().top}, 600);
      }
    },

    /**
     * Marks the global AppModel as unsaved.
     */
    markUnsaved: function () {
      Drupal.panels_ipe.app.set('unsaved', true);
    },

    /**
     * Changes the LayoutModel for this view.
     *
     * @param {Drupal.panels_ipe.LayoutModel} layout
     *   The new LayoutModel.
     */
    changeLayout: function (layout) {
      // Stop listening to the current model.
      this.stopListening(this.model);
      // Initialize with the new model.
      this.initialize({model: layout});
    },

    /**
     * Saves the current state of the layout to the tempstore.
     */
    saveToTempStore: function () {
      var model = this.model;
      var urlRoot = Drupal.panels_ipe.urlRoot(drupalSettings);
      var options = {url: urlRoot + '/layouts/' + model.get('id') + '/tempstore'};

      Backbone.sync('update', model, options);

      this.markUnsaved();
    },

    /**
     * Removes the block on the server via an AJAX call.
     *
     * @param {string} block_uuid
     *   The UUID/ID of a BlockModel.
     */
    removeServerSideBlock: function (block_uuid) {
      $.ajax({
        url: Drupal.panels_ipe.urlRoot(drupalSettings) + '/remove_block',
        method: 'DELETE',
        data: JSON.stringify(block_uuid),
        contentType: 'application/json; charset=UTF-8'
      });

      this.markUnsaved();
    },

    /**
     * Moves a block up or down in its RegionModel's BlockCollection.
     *
     * @param {Object} e
     *   The event object.
     */
    moveBlock: function (e) {
      // Get the BlockModel id (uuid).
      var id = this.getEventBlockUuid(e);

      // Get the direction the block is moving.
      var dir = $(e.currentTarget).data('action-id');

      // Grab the model for this region.
      var region_name = $(e.currentTarget).closest('[data-region-name]').data('region-name');
      var region = this.model.get('regionCollection').get(region_name);
      var block = region.getBlock(id);

      // Shift the Block.
      region.get('blockCollection').shift(block, dir);

      this.render();
      this.highlightBlock(id);

      this.saveToTempStore();
    },

    /**
     * Removes a Block from its region.
     *
     * @param {Object} e
     *   The event object.
     */
    removeBlock: function (e) {
      // Get the BlockModel id (uuid).
      var id = this.getEventBlockUuid(e);

      // Grab the model for this region.
      var region_name = $(e.currentTarget).closest('[data-region-name]').data('region-name');
      var region = this.model.get('regionCollection').get(region_name);

      // Remove the block.
      region.removeBlock(id);

      // Re-render ourselves.
      this.render();

      this.removeServerSideBlock(id);
    },

    /**
     * Configures an existing (on screen) Block.
     *
     * @param {Object} e
     *   The event object.
     */
    configureBlock: function (e) {
      // Get the BlockModel id (uuid).
      var id = this.getEventBlockUuid(e);

      // Grab the model for this region.
      var region_name = $(e.currentTarget).closest('[data-region-name]').data('region-name');
      var region = this.model.get('regionCollection').get(region_name);

      // Send an App-level event so our BlockPicker View can display a Form.
      Drupal.panels_ipe.app.trigger('configureBlock', region.getBlock(id));
    },

    /**
     * Edits an existing Content Block.
     *
     * @param {Object} e
     *   The event object.
     */
    editContentBlock: function (e) {
      // Get the BlockModel id (uuid).
      var id = this.getEventBlockUuid(e);

      // Get the blockModel content id.
      var plugin_id = this.getEventBlockId(e);

      // Split plugin id.
      var plugin_split = plugin_id.split(':');

      if (plugin_split[0] === 'block_content') {
        // Grab the model for this region.
        var region_name = $(e.currentTarget).closest('[data-region-name]').data('region-name');
        var region = this.model.get('regionCollection').get(region_name);

        // Send a App-level event so our BlockPicker View can respond and display a Form.
        Drupal.panels_ipe.app.trigger('editContentBlock', region.getBlock(id));
      }
    },

    /**
     * Reacts to a block being dropped on a droppable region.
     *
     * @param {Object} e
     *   The event object.
     * @param {Object} ui
     *   The jQuery UI object.
     */
    dropBlock: function (e, ui) {
      // Get the BlockModel id (uuid) and old region name.
      var id = ui.draggable.data('block-id');
      var old_region_name = ui.draggable.closest('[data-region-name]').data('region-name');

      // Get the BlockModel and remove it from its last position.
      var old_region = this.model.get('regionCollection').get(old_region_name);
      var block = old_region.getBlock(id);
      old_region.removeBlock(block, {silent: true});

      // Get the new region name and index from the droppable.
      var new_region_name = $(e.currentTarget).data('droppable-region-name');
      var index = $(e.currentTarget).data('droppable-index');

      // Add the BlockModel to its new region/index.
      var new_region = this.model.get('regionCollection').get(new_region_name);
      new_region.addBlock(block, {at: index, silent: true});

      // Re-render after the current execution cycle, to account for DOM editing
      // that jQuery.ui is going to do on this run. Modules like Contextual do
      // something similar to ensure rendering order is preserved.
      var self = this;
      window.setTimeout(function () {
        self.render();
        self.highlightBlock(id);
      });

      this.saveToTempStore();
    },

    /**
     * Adds a new BlockModel to the layout, or updates an existing Block model.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The new BlockModel
     * @param {string} region_name
     *   The region name that the block should be placed in.
     */
    addBlock: function (block, region_name) {
      // First, check if the Block already exists and remove it if so.
      var index = null;
      this.model.get('regionCollection').each(function (region) {
        var old_block = region.getBlock(block.get('uuid'));
        if (old_block) {
          index = region.get('blockCollection').indexOf(old_block);
          region.removeBlock(old_block);
        }
      });

      // Get the target region.
      var region = this.model.get('regionCollection').get(region_name);
      if (region) {
        // Add the block, at its previous index if necessary.
        var options = {};
        if (index !== null && index !== -1) {
          options.at = index;
        }
        region.addBlock(block, options);

        this.render();
        this.highlightBlock(block.get('uuid'), true);
      }
    }

  });

}(jQuery, _, Backbone, Drupal));
