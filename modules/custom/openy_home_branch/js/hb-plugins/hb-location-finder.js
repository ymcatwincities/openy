/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Sort handler for location-finder items.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchLocationFinderSort = {
    attach: function (context, settings) {
      $(document).once().on('hb-location-finder-sort', function (event, el) {
        var items = $(el).find('.views-row');
        items.each(function (index) {
          var weight = $(this).attr('data-hb-sort');
          if (weight == -1) {
            $(this).prependTo(el);
          }
        });
      });
    }
  };

  /**
   * Add plugin, that related to Location Finder.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-location-finder',
    attach: function (settings) {
      var locationsList = $(drupalSettings.home_branch.hb_location_finder.locationsList);
      locationsList.find(drupalSettings.home_branch.hb_location_finder.branchTeaserSelector)
        .each(function (index) {
          var el = $(this);
          // Add sort attribute with default index for correct order on change.
          el.closest('.views-row').attr('data-hb-sort', index);
          el.closest('.views-row').attr('data-hb-sort-origin', index);
        })
        .each(function () {
          // Attach plugin instance to each location teaser.
          // @see openy_home_branch/js/hb-plugin-base.js
          $(this).hbPlugin(settings);
        });

      // Trigger sort after plugin attach.
      $(document).trigger('hb-location-finder-sort', locationsList);
    },
    settings: {
      selector: '.hb-location-checkbox',
      event: 'change',
      element: null,
      branchTeaserSelector: drupalSettings.home_branch.hb_location_finder.branchTeaserSelector,
      selectedText: drupalSettings.home_branch.hb_location_finder.selectedText,
      notSelectedText: drupalSettings.home_branch.hb_location_finder.notSelectedText,
      init: function () {
        if (!this.element) {
          return;
        }
        var selected = Drupal.homeBranch.getValue('id');
        var branchEl = this.element.closest(this.branchTeaserSelector);
        if ($(this.element).val() === selected) {
          this.element.prop('checked', true);
          branchEl.addClass('hb-selected');
          branchEl.find('.hb-location-checkbox-wrapper label').text(this.selectedText);
          branchEl.closest('.views-row').attr('data-hb-sort', -1);
        }
        else {
          this.element.prop('checked', false);
          branchEl.removeClass('hb-selected');
          branchEl.find('.hb-location-checkbox-wrapper label').text(this.notSelectedText);
          branchEl.closest('.views-row').attr('data-hb-sort', branchEl.closest('.views-row').attr('data-hb-sort-origin'));
        }
      },
      onChange: function (event, el) {
        if ($(el).attr('id') == this.element.attr('id')) {
          // Save selected value in сookies storage.
          var id = ($(el).is(':checked')) ? $(el).val() : null;
          Drupal.homeBranch.setId(id);
        }
      },
      addMarkup: function (context) {
        var id = context.data('hb-id');
        var $markup = $('<div class="hb-location-checkbox-wrapper hb-checkbox-wrapper">' +
          '<input type="checkbox" value="' + id + '" id="hb-location-checkbox-' + id + '" class="hb-location-checkbox">' +
          '<label for="hb-location-checkbox-' + id + '">' + this.selectedText + '</label>' +
        '</div>');
        $markup.appendTo(context);
        // Save created element in plugin.
        this.element = $markup.find('input');
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
