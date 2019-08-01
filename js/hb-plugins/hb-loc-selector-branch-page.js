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
    attach: (settings) => {
      // Attach plugin instance to branch header instead of default
      // branch selector.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.branch-header').hbPlugin(settings);
    },
    settings: {
      selector: '.hb-location-checkbox',
      event: 'change',
      element: null,
      labelSelector: '.hb-location-checkbox-wrapper label span',
      selectedText: drupalSettings.home_branch.hb_loc_selector_branch_page.selectedText,
      notSelectedText: drupalSettings.home_branch.hb_loc_selector_branch_page.notSelectedText,
      placeholderSelector: drupalSettings.home_branch.hb_loc_selector_branch_page.placeholderSelector,
      init: function () {
        if (!this.element) {
          return;
        }
        let isSelected = $(this.element).val() === Drupal.homeBranch.getValue('id');
        this.element.prop('checked', isSelected);
        $(this.labelSelector).text(isSelected ? this.selectedText : this.notSelectedText);
      },
      handleChangeLink: function () {
        $('.hb-branch-selector-change').on('click', function () {
          Drupal.homeBranch.showModal();
        });
      },
      onChange: function (event, el) {
        // Save selected value in сookies storage.
        let id = ($(el).is(':checked')) ? $(el).val() : null;
        Drupal.homeBranch.setId(id);
      },
      addMarkup: function (context) {
        let id = $(context).data('hb-id');
        let branchSelector = $(this.placeholderSelector, context);
        // Replace branch selector implementation by home branch alternative.
        branchSelector.each(function() {
          $(this).replaceWith(`
              <div class="hb-branch-selector">
                <div class="hb-location-checkbox-wrapper">
                  <label>
                    <input type="checkbox" value="` + id + `" class="hb-location-checkbox hb-location-checkbox-` + id + `">
                    <span>` + this.selectedText + `</span>
                  </label>
                  <span>[<a class="hb-branch-selector-change" href="#">Change</a>]</span>
                </div>
              </div>
            `);
        });
        // Save created element in plugin.
        this.element = $('.hb-location-checkbox-' + id);
        this.handleChangeLink();
      },
    }
  });

})(jQuery, Drupal, drupalSettings);
