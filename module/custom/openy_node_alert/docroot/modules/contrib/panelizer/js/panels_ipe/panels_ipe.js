/**
 * @file
 * Entry point for the Panelizer IPE customizations.
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.panelizer = Drupal.panelizer || {};

  /**
   * @namespace
   */
  Drupal.panelizer.panels_ipe = {};

  /**
   * Make customizations to the Panels IPE for Panelizer.
   */
  Backbone.on('PanelsIPEInitialized', function() {
    // Disable the normal save event.
    Drupal.panels_ipe.app_view.stopListening(Drupal.panels_ipe.app.get('saveTab'), 'change:active');

    // Add a new revert tab model.
    if (drupalSettings.panelizer.user_permission.revert) {
      var revert_tab = new Drupal.panels_ipe.TabModel({title: 'Revert to default', id: 'revert'});
      Drupal.panels_ipe.app_view.tabsView.collection.add(revert_tab);

      // @todo: Put this into a proper view?
      Drupal.panels_ipe.app_view.listenTo(revert_tab, 'change:active', function () {
        var entity = drupalSettings.panelizer.entity;
        if (revert_tab.get('active') && !revert_tab.get('loading')) {
          if (confirm(Drupal.t('Are you sure you want to revert to default layout? All your layout changes will be lost for this node.'))) {
            // Remove our changes and refresh the page.
            revert_tab.set({loading: true});
            $.ajax({
              url: drupalSettings.path.baseUrl + 'admin/panelizer/panels_ipe/' + entity.entity_type_id + '/' + entity.entity_id + '/' + entity.view_mode + '/revert_to_default',
              data: {},
              type: 'POST'
            }).done(function (data) {
              location.reload();
            });
          }
          else {
            revert_tab.set('active', false, {silent: true});
          }
        }
      });

      // Hide the 'Revert to default' button if we're already on a default.
      if (drupalSettings.panels_ipe.panels_display.storage_type == 'panelizer_default') {
        revert_tab.set({hidden: true});
      }
    }

    // Hide the 'Revert to default' button if the user does not have permission.
    // if (!drupalSettings.panelizer.user_permission.revert) {
    //   revert_tab.set({hidden: true});
    // }

    // Add a new view for the save button to the TabsView.
    var tabs = {
      model: Drupal.panels_ipe.app_view.model,
      tabsView: Drupal.panels_ipe.app_view.tabsView,
    };
    if (typeof revert_tab !== 'undefined') {
      tabs.revertTab = revert_tab;
    }
    Drupal.panels_ipe.app_view.tabsView.tabViews['save'] = new Drupal.panelizer.panels_ipe.SaveTabView(tabs);
  });

}(jQuery, _, Backbone, Drupal));
