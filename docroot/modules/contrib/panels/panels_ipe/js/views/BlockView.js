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
      '<div class="ipe-actions-block ipe-actions" data-block-action-id="<%- uuid %>">' +
      '  <h5>Block: <%- label %></h5>' +
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
      '      <select><option>Move</option></select>' +
      '    </li>' +
      '    <li data-action-id="configure">' +
      '      <a><span class="ipe-icon ipe-icon-configure"></span></a>' +
      '    </li>' +
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
      this.listenTo(this.model, 'reset', this.render);
      this.listenTo(this.model, 'change:active', this.render);
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

      // Attach any Drupal behaviors.
      Drupal.attachBehaviors(this.el);

      // We modify our content if the IPE is active.
      if (this.model.get('active')) {
        // Prepend the ipe-actions header.
        this.$el.prepend(this.template_actions(this.model.toJSON()));

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

      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
