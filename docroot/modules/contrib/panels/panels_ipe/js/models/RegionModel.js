/**
 * @file
 * Base Backbone model for a Region.
 *
 * @todo Support sync operations to refresh a region, even if we don't have
 * a use case for that yet.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.RegionModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.RegionModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.RegionModel# */{

      /**
       * The machine name of the region.
       *
       * @type {string}
       */
      name: null,

      /**
       * The label of the region.
       *
       * @type {string}
       */
      label: null,

      /**
       * A BlockCollection for all blocks in this region.
       *
       * @type {Drupal.panels_ipe.BlockCollection}
       *
       * @see Drupal.panels_ipe.BlockCollection
       */
      blockCollection: null
    },

    /**
     * Checks if our BlockCollection contains a given Block UUID.
     *
     * @param {string} block_uuid
     *   The universally unique identifier of the block.
     *
     * @return {boolean}
     *   Whether the BlockCollection contains the block.
     */
    hasBlock: function (block_uuid) {
      return this.get('blockCollection').get(block_uuid) ? true : false;
    },

    /**
     * Gets a Block from our BlockCollection based on its UUID.
     *
     * @param {string} block_uuid
     *   The universally unique identifier of the block.
     *
     * @return {Drupal.panels_ipe.BlockModel|undefined}
     *   The block if it is inside this region.
     */
    getBlock: function (block_uuid) {
      return this.get('blockCollection').get(block_uuid);
    },

    /**
     * Removes a Block from our BlockCollection based on its UUID.
     *
     * @param {Drupal.panels_ipe.BlockModel|string} block
     *   The block or it's universally unique identifier.
     * @param {object} options
     *   Block related configuration.
     */
    removeBlock: function (block, options) {
      this.get('blockCollection').remove(block, options);
    },

    /**
     * Adds a new BlockModel to our BlockCollection.
     *
     * @param {Drupal.panels_ipe.BlockModel} block
     *   The block that needs to be added.
     * @param {object} options
     *   Block related configuration.
     */
    addBlock: function (block, options) {
      this.get('blockCollection').add(block, options);
    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.RegionCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.RegionCollection# */{

    /**
     * @type {Drupal.panels_ipe.RegionModel}
     */
    model: Drupal.panels_ipe.RegionModel,

    /**
     * For Regions, our identifier is the region name.
     *
     * @type {function}
     *
     * @param {Object} attrs
     *   The current RegionModel's attributes.
     *
     * @return {string}
     *   The current RegionModel's name attribute.
     */
    modelId: function (attrs) {
      return attrs.name;
    }

  });

}(_, jQuery, Backbone, Drupal));
