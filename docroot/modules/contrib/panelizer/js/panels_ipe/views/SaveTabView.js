/**
 * @file
 * Contains Drupal.panelizer.panels_ipe.SaveTabView.
 */

(function ($, _, Backbone, Drupal) {
  'use strict';

  Drupal.panelizer.panels_ipe.SaveTabView = Backbone.View.extend(/** @lends Drupal.panelizer.panels_ipe.SaveTabView# */{

    /**
     * @type {function}
     */
    template: function() {
      var saveButtons = '';

      // Don't show the 'save as default' button if the user doesn't have
      // permission.
      if (drupalSettings.panelizer.user_permission.save_default) {
        saveButtons += '<div class="panelizer-ipe-save-button"><a class="panelizer-ipe-save-custom" href="#">Save as custom</a></div>';
        saveButtons += '<div class="panelizer-ipe-save-button"><a class="panelizer-ipe-save-default" href="#">Save as default</a></div>';
      }
      else {
        saveButtons += '<div class="panelizer-ipe-save-button"><a class="panelizer-ipe-save-custom" href="#">Save</a></div>';
      }

      return saveButtons;
    },

    /**
     * @type {Drupal.panels_ipe.AppModel}
     */
    model: null,

    /**
     * @type {Drupal.panels_ipe.TabsView}
     */
    tabsView: null,

    /**
     * @type {Drupal.panels_ipe.TabModel}
     */
    revertTab: null,

    /**
     * @type {object}
     */
    events: {
      'click .panelizer-ipe-save-custom': 'saveCustom',
      'click .panelizer-ipe-save-default': 'saveDefault'
    },

    /**
     * @type {function}
     */
    onClick: function () {
      var entity = drupalSettings.panelizer.entity;
      if (this.model.get('saveTab').get('active')) {
        // If only one option is available, then just do that directly.
        if (!entity.panelizer_default_storage_id) {
          this._save('panelizer_field');
        }
        else if (!entity.panelizer_field_storage_id) {
          // Only if the user has permission.
          if (drupalSettings.panelizer.user_permission.save_default) {
            this._save('panelizer_default');
          }
        }
      }
    },

    /**
     * @type {function}
     */
    saveCustom: function () {
      this._save('panelizer_field');
    },

    /**
     * @type {function}
     */
    saveDefault: function () {
      this._save('panelizer_default');
    },

    /**
     * @type {function}
     */
    _save: function (storage_type) {
      var self = this,
          layout = this.model.get('layout');

      // Give the backend enough information to save in the correct way.
      layout.set('panelizer_save_as', storage_type);
      layout.set('panelizer_entity', drupalSettings.panelizer.entity);

      if (this.model.get('saveTab').get('active')) {
        // Save the Layout and disable the tab.
        this.model.get('saveTab').set({loading: true, active: false});
        this.tabsView.render();
        layout.save().done(function () {
          self.model.get('saveTab').set({loading: false});
          self.model.set('unsaved', false);

          // Change the storage type and id for the next save.
          drupalSettings.panels_ipe.panels_display.storage_type = storage_type;
          drupalSettings.panels_ipe.panels_display.storage_id = drupalSettings.panelizer.entity[storage_type + '_storage_id'];
          Drupal.panels_ipe.setUrlRoot(drupalSettings);

          // Show/hide the revert to default tab.
          self.revertTab.set({hidden: storage_type === 'panelizer_default'});
          self.tabsView.render();
        });
      }
    },

    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {Object} options
     *   An object containing the following keys:
     * @param {Drupal.panels_ipe.AppModel} options.model
     *   The app state model.
     * @param {Drupal.panels_ipe.TabsView} options.tabsView
     *   The app view.
     * @param {Drupal.panels_ipe.TabModel} options.revertTab
     *   The revert tab.
     */
    initialize: function (options) {
      this.model = options.model;
      this.tabsView = options.tabsView;
      this.revertTab = options.revertTab;

      this.listenTo(this.model.get('saveTab'), 'change:active', this.onClick);
    },

    /**
     * Renders the selection menu for picking Layouts.
     *
     * @return {Drupal.panelizer.panels_ipe.SaveTabView}
     *   Return this, for chaining.
     */
    render: function () {
      this.$el.html(this.template());
      return this;
    }

  });

}(jQuery, _, Backbone, Drupal));
