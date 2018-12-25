/**
 * @file
 * A .
 *
 * @see Drupal.panels_ipe.TabView
 */

(function (Backbone, Drupal) {

  'use strict';

  /**
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.panels_ipe.TabModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.TabModel# */{

    /**
     * @type {object}
     *
     * @prop {bool} active
     * @prop {string} title
     */
    defaults: /** @lends Drupal.panels_ipe.TabModel# */{

      /**
       * The ID of the tab.
       *
       * @type {int}
       */
      id: null,

      /**
       * Whether or not the tab is active.
       *
       * @type {bool}
       */
      active: false,

      /**
       * Whether or not the tab is hidden.
       *
       * @type {bool}
       */
      hidden: false,

      /**
       * Whether or not the tab is loading.
       *
       * @type {bool}
       */
      loading: false,

      /**
       * The title of the tab.
       *
       * @type {string}
       */
      title: null
    }

  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.panels_ipe.TabCollection = Backbone.Collection.extend(/** @lends Drupal.panels_ipe.TabCollection# */{

    /**
     * @type {Drupal.panels_ipe.TabModel}
     */
    model: Drupal.panels_ipe.TabModel
  });

}(Backbone, Drupal));
