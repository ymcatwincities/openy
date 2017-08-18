/**
 * @file
 * Attaches behavior for the Panels IPE module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Override Panels IPE Content manager.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openy_ds_panels_ipe = {
    attach: function (context, settings) {
      $('body').once('openy-ds-panels-ipe-init').each(function () {
        if (typeof Drupal.panels_ipe.app_view.tabsView.tabViews.manage_content === 'undefined') {
          return;
        }
        Drupal.panels_ipe.app_view.tabsView.tabViews.manage_content = new Drupal.panels_ipe.OpenYDSBlockPicker();
      });
    }
  };

}(jQuery, Drupal));
