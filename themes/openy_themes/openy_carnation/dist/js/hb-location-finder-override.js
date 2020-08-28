/**
 * @file
 * Carnation overrides Home branch Location finder plugin.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Override hb-location-finder.js
   */
  if (Drupal.homeBranch.plugins.length > 0) {
    for (var key in Drupal.homeBranch.plugins) {
      // Find `hb-location-finder` in home branch plugins list.
      if (Drupal.homeBranch.plugins.hasOwnProperty(key) && Drupal.homeBranch.plugins[key].name === 'hb-location-finder') {
        // Also, we can override component markup.
        // @see openy_home_branch/js/hb-plugins/hb-location-finder.js
        Drupal.homeBranch.plugins[key].settings.addMarkup = function (context) {
          var id = context.data('hb-id');
          var $markup = $(`
              <div class="hb-location-checkbox-wrapper hb-checkbox-wrapper">
                <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox">
                <label for="hb-location-checkbox-` + id + `">` + this.selectedText + `</label>
              </div>
              `);
          $markup.insertAfter(context.find('.location-item--title'));
          // Save created element in plugin.
          this.element = $markup.find('input');
        };
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
