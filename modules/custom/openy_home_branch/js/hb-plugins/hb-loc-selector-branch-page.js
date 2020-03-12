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
    name: 'hb-loc-selector-branch-page',
    attach: function (settings) {
      // Attach plugin instance to branch header instead of default
      // branch selector.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.branch-header').hbPlugin(settings);
    },
    settings: {
      selector: '.hb-location-checkbox',
      event: 'change',
      element: null,
      wrapper: null,
      labelSelector: '.hb-location-checkbox-wrapper label',
      selectedText: drupalSettings.home_branch.hb_loc_selector_branch_page.selectedText,
      notSelectedText: drupalSettings.home_branch.hb_loc_selector_branch_page.notSelectedText,
      placeholderSelector: drupalSettings.home_branch.hb_loc_selector_branch_page.placeholderSelector,
      selectedClass: 'hb-branch-selector--selected',
      init: function () {
        if (!this.element) {
          return;
        }
        var isSelected = $(this.element).val() === Drupal.homeBranch.getValue('id');
        this.element.prop('checked', isSelected);
        $(this.labelSelector).text(isSelected ? this.selectedText : this.notSelectedText);

        this.wrapper.removeClass(this.selectedClass);
        if (isSelected) {
          this.wrapper.addClass(this.selectedClass);
        }
      },
      handleChangeLink: function () {
        $('.hb-branch-selector-change').on('click', function () {
          Drupal.homeBranch.showModal(true);
          return false;
        });
      },
      onChange: function (event, el) {
        // Save selected value in сookies storage.
        var id = ($(el).is(':checked')) ? $(el).val() : null;
        Drupal.homeBranch.setId(id);
      },
      addMarkup: function (context) {
        var id = $(context).data('hb-id');
        var branchSelector = $(this.placeholderSelector, context);
        // Replace branch selector implementation by home branch alternative.
        branchSelector.each(function () {
          $(this).replaceWith('<div class="hb-branch-selector">' +
            '<div class="hb-location-checkbox-wrapper">' +
              '<span class="hb-checkbox-wrapper">' +
                '<input type="checkbox" value="' + id + '" id="hb-location-checkbox-' + id + '" class="hb-location-checkbox hb-location-checkbox-' + id + '">' +
                '<label for="hb-location-checkbox-' + id + '">' + this.selectedText + '</label>' +
              '</span>' +
              '<span class="hb-branch-selector-change-wrapper">[<a class="hb-branch-selector-change" href="#">Change</a>]</span>' +
            '</div>' +
          '</div>');
        });
        // Save created element in plugin.
        this.element = $('.hb-location-checkbox-' + id);
        this.wrapper = $('.hb-branch-selector');
        this.handleChangeLink();
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
