/**
 * @file
 * Location finder home branch extension override example.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Override hb-location-finder.js plugin.
   *
   * @type {Drupal~behavior}
   */
  if (Drupal.homeBranch.plugins.length > 0) {
    for (var key in Drupal.homeBranch.plugins) {
      // First of all we need to find `hb-location-finder` in home branch
      // plugins list.
      if (Drupal.homeBranch.plugins.hasOwnProperty(key) && Drupal.homeBranch.plugins[key]['name'] === 'hb-location-finder') {
        // We can override any settings property.
        // For example - change selectedText value:
        Drupal.homeBranch.plugins[key]['settings']['selectedText'] = 'My Home Branch (NEW)';
        // Also, we can override component markup.
        // @see openy_home_branch/js/hb-plugins/hb-location-finder.js
        Drupal.homeBranch.plugins[key]['settings']['addMarkup'] = function (context) {
          let id = context.data('hb-id');
          let $markup = $(`
                  <div class="hb-location-checkbox-wrapper">
                    <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox">
                    <label for="hb-location-checkbox-` + id + `">` + this.selectedText + `</label>
                    <span>TEST override</span>
                  </div>
                `);
          $markup.appendTo(context);
          this.element = $markup.find('input');
        }
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
