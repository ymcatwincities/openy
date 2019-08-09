/**
 * @file
 * Home branch extension override example.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Override hb-loc-selector-branch-page.js
   */
  if (Drupal.homeBranch.plugins.length > 0) {
    for (var key in Drupal.homeBranch.plugins) {
      // Find `hb-loc-selector-branch-page` in home branch plugins list.
      if (Drupal.homeBranch.plugins.hasOwnProperty(key) && Drupal.homeBranch.plugins[key].name === 'hb-loc-selector-branch-page') {
        // Also, we can override component markup.
        // @see openy_home_branch/js/hb-plugins/hb-loc-selector-branch-page.js
        Drupal.homeBranch.plugins[key]['settings']['addMarkup'] = function (context) {
          var id = $(context).data('hb-id');
          var branchTitle = $('.location-masthead .masthead-brand', context);
          // Insert hb-branch-selector after title (For mobile and desktop).
          branchTitle.each(function() {
            $(this).after(`
              <div class="hb-branch-selector">
                <div class="hb-location-checkbox-wrapper">
                  <span class="hb-checkbox-wrapper">
                    <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox hb-location-checkbox-` + id + `">
                    <label for="hb-location-checkbox-` + id + `">` + this.selectedText + `</label>
                  </span>
                  <span class="hb-branch-selector-change-wrapper">[<a class="hb-branch-selector-change" href="#">Change</a>]</span>
                </div>
              </div>
            `);
          });
          // Save created element in plugin.
          this.element = $('.hb-location-checkbox-' + id);
          this.wrapper = $('.hb-branch-selector');
          this.handleChangeLink();
        }
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
