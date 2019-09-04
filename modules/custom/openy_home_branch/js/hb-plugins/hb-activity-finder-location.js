/**
 * @file
 * Home branch Activity Finder extension.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Adds plugin related to Activity finder paragraph.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-loc-selector-activity-finder',
    attach: function (settings) {
      // Attach plugin instance to activity finder paragraph.
      // @see openy_home_branch/js/hb-plugin-base.js
      $(drupalSettings.home_branch.hb_loc_selector_activity_finder.selector).hbPlugin(settings);
    },
    settings: {
      selector: null,
      locationStep: drupalSettings.home_branch.hb_loc_selector_activity_finder.locationStep,
      event: null,
      element: null,
      getQueryParam: function (name) {
        // Get url query param by name.
        var results = new RegExp('[\?&]' + name + '=([^&#]*)')
          .exec(window.location.search);
        return (results !== null) ? results[1] || 0 : false;
      },
      replaceQueryParam: function (param, newval, search) {
        // Set url query param by name.
        var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
        var query = search.replace(regex, "$1").replace(/&$/, '');
        return (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
      },
      checkStep: function (self) {
        if (self.getQueryParam('step') != self.locationStep) {
          // Skip if we not on location select step.
          return;
        }
        // Get selected Home Branch.
        var selected = Drupal.homeBranch.getValue('id');
        if (!selected) {
          // Skip if Home Branch not selected.
          return;
        }

        if (typeof window.OpenY.field_prgf_af_results_ref === 'undefined') {
          // Skip if result page not set.
          return;
        }

        // Redirect to results page with selected Home Branch in filters.
        var resultsUrl = window.OpenY.field_prgf_af_results_ref[0]['url'];
        window.location = resultsUrl + self.replaceQueryParam('locations', selected, window.location.search);
      },
      init: function () {
        window.setInterval(this.checkStep, 2000, this);
      }
    },
  });

})(jQuery, Drupal, drupalSettings);
