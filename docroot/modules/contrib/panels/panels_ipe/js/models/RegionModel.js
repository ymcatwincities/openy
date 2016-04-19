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
