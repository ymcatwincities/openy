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
      replaceQueryParam: function (param, newval, search) {
        // Set url query param by name.
        var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
        var query = search.replace(regex, "$1").replace(/&$/, '');
        return (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
      },
      checkStep: function (self) {
        if (window.location.hash.indexOf(self.locationStep) === -1) {
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
        var queryString = window.location.hash.replace(self.locationStep, '');
        window.location = resultsUrl + self.replaceQueryParam('locations', selected, queryString);
      },
      init: function () {
        window.setInterval(this.checkStep, 2000, this);
      }
    },
  });

})(jQuery, Drupal, drupalSettings);
