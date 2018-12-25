/**
 * @file
 * The primary Backbone model for Panels IPE.
 *
 * @see Drupal.panels_ipe.AppView
 */

(function (Backbone, Drupal) {

  'use strict';

  /**
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.panels_ipe.AppModel = Backbone.Model.extend(/** @lends Drupal.panels_ipe.AppModel# */{

    /**
     * @type {object}
     *
     * @prop {bool} active
     * @prop {Drupal.panels_ipe.TabModel} activeTab
     * @prop {Drupal.panels_ipe.BlockModel} activeBlock
     * @prop {Drupal.panels_ipe.RegionModel} activeRegion
     */
    defaults: /** @lends Drupal.panels_ipe.AppModel# */{

      /**
       * Whether or not the editing part of the application is active.
       *
       * @type {bool}
       */
      active: false,

      /**
       * The current Layout.
       *
       * @type {Drupal.panels_ipe.LayoutModel}
       */
      layout: null,

      /**
       * A collection of all tabs on screen.
       *
       * @type {Drupal.panels_ipe.TabCollection}
       */
      tabCollection: null,

      /**
       * The "Locked" tab.
       *
       * @type {Drupal.panels_ipe.TabModel}
       */
      lockedTab: null,

      /**
       * The "Edit" tab.
       *
       * @type {Drupal.panels_ipe.TabModel}
       */
      editTab: null,

      /**
       * The "Save" tab.
       *
       * @type {Drupal.panels_ipe.TabModel}
       */
      saveTab: null,

      /**
       * The "Cancel" tab.
       *
       * @type {Drupal.panels_ipe.TabModel}
       */
      cancelTab: null,

      /**
       * Whether or not there are unsaved changes.
       *
       * @type {bool}
       */
      unsaved: false
    }

  });

}(Backbone, Drupal));
