/**
 * @file
 * Base Backbone model for a Block.
 */

(function (_, $, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.panels_ipe.BlockModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.BlockModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.BlockModel# */{

      /**
       * The block state.
       *
       * @type {bool}
       */
      active: false,

      /**
       * The ID of the block.
       *
       * @type {string}
       */
      id: null,

      /**
       * The unique ID of the block.
       *
       * @type {string}
       */
      uuid: null,

      /**
       * The label of the block.
       *
       * @type {string}
       */
      label: null,

      /**
       * The provider for the block (usually the module name).
       *
       * @type {string}
       */
      provider: null,

      /**
       * The ID of the plugin for this block.
       *
       * @type {string}
       */
      plugin_id: null,

      /**
       * The HTML content of the block. This is stored in the model as the
       * IPE doesn't actually care what the block's content is, the functional
       * elements of the model are the metadata. The BlockView renders this
       * wrapped inside IPE elements.
       *
       * @type {string}
       */
      html: null,

      /**
       * Whether or not this block is currently in a syncing state.
       *
       * @type {bool}
       */
      syncing: false

    },

    /**
     * @type {function}
     *
     * @return {string}
     *   A URL that can be used to refresh this Block model. Only fetch methods
     *   are supported currently.
     */
    url: function () {
      return Drupal.panels_ipe.urlRoot(drupalSettings) + '/block/' + this.get('uuid');
    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.BlockCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.BlockCollection# */{

    /**
     * @type {Drupal.panels_ipe.BlockModel}
     */
    model: Drupal.panels_ipe.BlockModel,

    /**
     * For Blocks, our identifier is the UUID, not the ID.
     *
     * @type {function}
     *
     * @param {Object} attrs
     *   The attributes of the current model in the collection.
     *
     * @return {string}
     *   The value of a BlockModel's UUID.
     */
    modelId: function (attrs) {
      return attrs.uuid;
    },

    /**
     * Moves a Block up or down in this collection.
     *
     * @type {function}
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *  The BlockModel you want to move.
     * @param {string} direction
     *  The string name of the direction (either "up" or "down").
     */
    shift: function (block, direction) {
      var index = this.indexOf(block);
      if ((direction === 'up' && index > 0) || (direction === 'down' && index < this.models.length)) {
        this.remove(block, {silent: true});
        var new_index = direction === 'up' ? index - 1 : index + 1;
        this.add(block, {at: new_index, silent: true});
      }
    }

  });

}(_, jQuery, Backbone, Drupal, drupalSettings));
