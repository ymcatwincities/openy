/**
 * @file
 * Cookies storage JavaScript for the Open Y Home Branch module.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.homeBranch = {

    // Home branch data.
    data: {
      id: null,
      dontAsk: false
    },

    showModal: false,

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
      let self = this;
      $.getJSON(drupalSettings.path.baseUrl + 'api/home-branch/locations', function (data) {
        data.forEach(function (item) {
          self.locations[item.nid] = item.title;
        });
      });
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
      $.cookie('home_branch', JSON.stringify(this.data), { expires: 360 });
      $(document).trigger('hb-after-storage-update', this.data);
    },

    // Load data from Cookies storage.
    loadFromStorage: function () {
      let data = $.cookie('home_branch');
      if (data !== undefined) {
        return JSON.parse($.cookie('home_branch'));
      }
      // Return default values if storage not defined.
      return this.data;
    },
  };

  /**
   * Init home branch storage on load.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchCookiesStorage = {
    attach(context, settings) {
      if (typeof Drupal.homeBranch === 'undefined') {
        return;
      }

      $(context).find('body').once('home-branch-cookies-storage').each(function () {
        // Init Home Branch Cookies storage on page load.
        Drupal.homeBranch.init();
      });
    }
  }

})(jQuery, Drupal);
