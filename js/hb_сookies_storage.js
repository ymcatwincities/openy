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
      name: null,
      dontAsk: false
    },

    // Object Initialization.
    init: function () {
      this.data = this.loadFromStorage();
    },

    // Get property value.
    getValue: function (property) {
      return this.data[property];
    },

    // Set property value.
    setValue: function (property, value) {
      this.data[property] = value;
      this.updateStorage();
    },

    // Set property value.
    setById: function (id) {
      // TODO: Update storage only with ID (ID + NAME)
      // TODO: Get name from some were.
      this.setValue('id', id);
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
