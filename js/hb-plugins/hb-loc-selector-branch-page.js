/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Add plugin, that related to Location Finder.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-menu-selector',
    attach: (context) => {

      // Attach plugin instance to branch header instead of default
      // branch selector.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.branch-header', context).each(function (index) {
        let id = $(this).attr('data-hb-id');
        $(this).find('.hb-branch-selector').hbPlugin({
          selector: '.hb-location-checkbox',
          event: 'change',
          element: null,
          init: function () {
            if (!this.element) {
              return;
            }
            let selected = Drupal.homeBranch.getValue('id');
            if ($(this.element).val() === selected) {
              this.element.prop('checked', true);
              $('.hb-location-checkbox-wrapper label').text('My Home Branch');
            }
            else {
              this.element.prop('checked', false);
              $('.hb-location-checkbox-wrapper label').text('Set as my Home Branch');
            }
            // This text also changed in hb-menu-selector plugin,
            // so we need to set default value after this.
            $('.hb-location-checkbox-wrapper a.hb-menu-selector').text('Change');
          },
          onChange: function (event, el) {
            // Save selected value in сookies storage.
            let id = ($(el).is(':checked')) ? $(el).val() : null;
            Drupal.homeBranch.setId(id);
          },
          addMarkup: function (context) {
            let settings = drupalSettings.home_branch.hb_loc_selector_branch_page;
            let branchSelector = $(settings.selector, context);
            // Replace branch selector implementation by home branch alternative.
            branchSelector.replaceWith(`
              <div class="hb-branch-selector">
                <div class="hb-location-checkbox-wrapper">
                  <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox">
                  <label>Set as my Home Branch</label>
                  <span>[<a class="hb-menu-selector" href="#">Change</a>]</span>
                </div>
              </div>
            `);
            // Save created element in plugin.
            this.element = $('#hb-location-checkbox-' + id);
          },
        });
      });

    },
  });

})(jQuery, Drupal, drupalSettings);
