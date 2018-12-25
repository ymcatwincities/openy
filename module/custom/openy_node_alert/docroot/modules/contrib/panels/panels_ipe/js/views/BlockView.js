/**
 * @file
 * The primary Backbone view for a Block.
 *
 * see Drupal.panels_ipe.BlockModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.BlockView = Backbone.View.extend(/** @lends Drupal.panels_ipe.BlockView# */{

    /**
     * @type {function}
     */
    template_actions: _.template(
      '<div class="ipe-actions-block ipe-actions" data-block-action-id="<%- uuid %>" data-block-edit-id="<%- id %>">' +
      '  <h5>' + Drupal.t('Block: <%- label %>') + '</h5>' +
      '  <ul class="ipe-action-list">' +
      '    <li data-action-id="remove">' +
      '      <a><span class="ipe-icon ipe-icon-remove"></span></a>' +
      '    </li>' +
      '    <li data-action-id="up">' +
      '      <a><span class="ipe-icon ipe-icon-up"></span></a>' +
      '    </li>' +
      '    <li data-action-id="down">' +
      '      <a><span class="ipe-icon ipe-icon-down"></span></a>' +
      '    </li>' +
      '    <li data-action-id="move">' +
      '      <select><option>' + Drupal.t('Move') + '</option></select>' +
      '    </li>' +
      '    <li data-action-id="configure">' +
      '      <a><span class="ipe-icon ipe-icon-configure"></span></a>' +
      '    </li>' +
      '<% if (plugin_id === "block_content" && edit_access) { %>' +
      '    <li data-action-id="edit-content-block">' +
      '      <a><span class="ipe-icon ipe-icon-edit"></span></a>' +
      '    </li>' +
      '<% } %>' +
      '  </ul>' +
      '</div>'
    ),

    /**
     * @type {Drupal.panels_ipe.BlockModel}
     */
    model: null,

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.panels_ipe.BlockModel} options.model
     *   The block state model.
     * @param {string} options.el
     *   An optional selector if an existing element is already on screen.
     */
    initialize: function (options) {
      this.model = options.model;
      // An element already exists and our HTML properly isn't set.
      // This only occurs on initial page load for performance reasons.
      if (options.el && !this.model.get('html')) {
        this.model.set({html: this.$el.prop('outerHTML')});
      }
      this.listenTo(this.model, 'sync', this.finishedSync);
      this.listenTo(this.model, 'change:syncing', this.render);
    },

    /**
     * Renders the wrapping elements and refreshes a block model.
     *
     * @return {Drupal.panels_ipe.BlockView}
     *   Return this, for chaining.
     */
    render: function () {
      // Replace our current HTML.
      this.$el.replaceWith(this.model.get('html'));
      this.setElement("[data-block-id='" + this.model.get('uuid') + "']");

      // We modify our content if the IPE is active.
      if (this.model.get('active')) {
        // Prepend the ipe-actions header.
        var template_vars = this.model.toJSON();
        template_vars['edit_access'] = drupalSettings.panels_ipe.user_permission.create_content;
        this.$el.prepend(this.template_actions(template_vars));

        // Add an active class.
        this.$el.addClass('active');

        // Make ourselves draggable.
        this.$el.draggable({
          scroll: true,
          scrollSpeed: 20,
          // Maintain our original width when dragging.
          helper: function (e) {
            var original = $(e.target).hasClass('ui-draggable') ? $(e.target) : $(e.target).closest('.ui-draggable');
            return original.clone().css({
              width: original.width()
            });
          },
          start: function (e, ui) {
            $('.ipe-droppable').addClass('active');
            // Remove the droppable regions closest to this block.
            $(e.target).next('.ipe-droppable').removeClass('active');
            $(e.target).prev('.ipe-droppable').removeClass('active');
          },
          stop: function (e, ui) {
            $('.ipe-droppable').removeClass('active');
          },
          opacity: .5
        });
      }

      // Add a special class if we're currently syncing HTML from the server.
      if (this.model.get('syncing')) {
        this.$el.addClass('syncing');
      }

      return this;
    },

    /**
     * Overrides the default remove function to make a copy of our current HTML
     * into the Model for future rendering. This is required as modules like
     * Quickedit modify Block HTML without our knowledge.
     *
     * @return {Drupal.panels_ipe.BlockView}
     *   Return this, for chaining.
     */
    remove: function () {
      // Remove known augmentations to HTML so that they do not persist.
      this.$('.ipe-actions-block').remove();
      this.$el.removeClass('ipe-highlight active');

      // Update our Block model HTML based on our current visual state.
      this.model.set({html: this.$el.prop('outerHTML')});

      // Call the normal Backbow.view.remove() routines.
      this._removeElement();
      this.stopListening();
      return this;
    },

    /**
     * Reacts to our model being synced from the server.
     */
    finishedSync: function () {
      this.model.set('syncing', false);
    }

  });

}(jQuery, _, Backbone, Drupal));
