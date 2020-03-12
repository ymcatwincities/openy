/**
 * @file
 * Cookies storage JavaScript for the Open Y Home Branch module.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.homeBranch = {

    // Home branch data.
    data: {
      id: null,
      dontAsk: false,
      lastShowTime: 0
    },

    // Locations list.
    locations: {},

    // Plugins that can be provided by other components.
    // @see openy_home_branch/js/hb-plugins/hb-location-finder.js
    // Drupal.homeBranch.plugins.push.
    plugins: [],

    // Object Initialization.
    init: function () {
      this.data = this.loadFromStorage();

      // Get locations list.
      var self = this;
      self.locations = drupalSettings.home_branch.hb_menu_selector.locations;
      setTimeout(function() {
        self.attachPlugins();
      }, 0);
    },

    // Attaches plugins.
    attachPlugins: function () {
      if (this.plugins.length === 0) {
        return;
      }
      Drupal.homeBranch.plugins.forEach(function (plugin, key, arr) {
        plugin.attach(plugin.settings);
      });
      if (this.data['id'] == null && !this.data['dontAsk']) {
        this.showModal();
      }
    },

    // Get property value.
    getValue: function (property) {
      return this.data[property];
    },

    // Set values.
    set: function (id, dontAsk) {
      this.data.id = id;
      this.data.dontAsk = dontAsk;
      this.updateStorage();
    },

    // Set property value.
    setValue: function (property, value) {
      this.data[property] = value;
      this.updateStorage();
    },

    // Set property value.
    setId: function (id) {
      this.setValue('id', id);
    },

    // Get locations list.
    getLocations: function () {
      return this.locations;
    },

    // Move current data to Cookies storage.
    updateStorage: function () {
      $(document).trigger('hb-before-storage-update', this.data);
      $.cookie('home_branch', JSON.stringify(this.data), { expires: 360, path: drupalSettings.path.baseUrl });
      $(document).trigger('hb-after-storage-update', this.data);
    },

    // Load data from Cookies storage.
    loadFromStorage: function () {
      var data = $.cookie('home_branch');
      if (data !== undefined) {
        return JSON.parse($.cookie('home_branch'));
      }
      // Return default values if storage not defined.
      return this.data;
    },

    // Request to show modal window.
    showModal: function () {
      // Intentionally empty.
    }
  };

  /**
   * Init home branch storage on load.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchCookiesStorage = {
    attach: function (context, settings) {
      if (typeof Drupal.homeBranch === 'undefined') {
        return;
      }

      $(context).find('body').once('home-branch-cookies-storage').each(function () {
        // Init Home Branch Cookies storage on page load.
        Drupal.homeBranch.init();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
