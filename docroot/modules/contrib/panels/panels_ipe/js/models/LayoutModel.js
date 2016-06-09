/**
 * @file
 * Base Backbone model for a Layout.
 */

(function (_, $, Backbone, Drupal) {

  'use strict';

  Drupal.panels_ipe.LayoutModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.LayoutModel# */{

    /**
     * @type {object}
     */
    defaults: /** @lends Drupal.panels_ipe.LayoutModel# */{

      /**
       * The layout machine name.
       *
       * @type {string}
       */
      id: null,

      /**
       * Whether or not this was the original layout for the variant.
       *
       * @type {bool}
       */
      original: false,

      /**
       * The layout label.
       *
       * @type {string}
       */
      label: null,

      /**
       * The layout icon.
       *
       * @type {string}
       */
      icon: null,

      /**
       * Whether or not this is the current layout.
       *
       * @type {bool}
       */
      current: false,

      /**
       * The wrapping HTML for this layout. Only used for initial rendering.
       *
       * @type {string}
       */
      html: null,

      /**
       * A collection of regions contained in this Layout.
       *
       * @type {Drupal.panels_ipe.RegionCollection}
       */
      regionCollection: null,

      /**
       * An array of Block UUIDs that we need to delete.
       *
       * @type {Array}
       */
      deletedBlocks: []


    },

    /**
     * Overrides the isNew method to mark if this is the initial layout or not.
     *
     * @return {bool}
     *   A boolean which determines if this Block was on the page on load.
     */
    isNew: function () {
      return !this.get('original');
    },

    /**
     * Overrides the parse method to set our regionCollection dynamically.
     *
     * @param {Object} resp
     *   The decoded JSON response from the backend server.
     * @param {Object} options
     *   Additional options passed to parse.
     *
     * @return {Object}
     *   An object representing a LayoutModel's attributes.
     */
    parse: function (resp, options) {
      // If possible, initialize our region collection.
      if (typeof resp.regions != 'undefined') {
        resp.regionCollection = new Drupal.panels_ipe.RegionCollection();
        for (var i in resp.regions) {
          if (resp.regions.hasOwnProperty(i)) {
            var region = new Drupal.panels_ipe.RegionModel(resp.regions[i]);
            region.set({blockCollection: new Drupal.panels_ipe.BlockCollection()});
            resp.regionCollection.add(region);
          }
        }
      }
      return resp;
    },

    /**
     * @type {function}
     *
     * @return {string}
     *   A URL that can be used to refresh this Layout's attributes.
     */
    url: function () {
      return Drupal.panels_ipe.urlRoot(drupalSettings) + '/layouts/' + this.get('id');
    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.LayoutCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.LayoutCollection# */{

    /**
     * @type {Drupal.panels_ipe.LayoutModel}
     */
    model: Drupal.panels_ipe.LayoutModel,

    /**
     * @type {function}
     *
     * @return {string}
     *   A URL that can be used to refresh this collection's child models.
     */
    url: function () {
      return Drupal.panels_ipe.urlRoot(drupalSettings) + '/layouts';
    }

  });

}(_, jQuery, Backbone, Drupal));
