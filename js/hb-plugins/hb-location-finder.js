/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Sort handler for location-finder items.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchLocationFinderSort = {
    attach(context, settings) {
      $(document).once().on('hb-location-finder-sort', function (event, el) {
        let items = $(el).find('.views-row');
        items.each(function (index) {
          let weight = $(this).attr('data-hb-sort');
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
    attach: (context) => {
      // TODO: get selector from settings.
      let locationsList = $('.field-prgf-location-finder .locations-list .views-row__wrapper', context);
      locationsList.find('.views-row .node--type-branch')
        .each(function (index) {
          let el = $(this);
          // Add sort attribute with default index for correct order on change.
          el.closest('.views-row').attr('data-hb-sort', index);
          el.closest('.views-row').attr('data-hb-sort-origin', index);
          //el.closest('.views-row').find('.location-item--title').prepend(index);
        })
        .each(function() {
          // Attach plugin instance to each location teaser.
          // @see openy_home_branch/js/hb-plugin-base.js
          $(this).hbPlugin({
            selector: '.hb-location-checkbox',
            event: 'change',
            element: null,
            branchTeaserSelector: '.node--type-branch.node--view-mode-teaser',
            selectedText: 'My Home Branch',
            notSelectedText: 'Set as my Home Branch',
            init: function () {
              if (!this.element) {
                return;
              }
              let selected = Drupal.homeBranch.getValue('id');
              let branchEl = this.element.closest(this.branchTeaserSelector);
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
                let id = ($(el).is(':checked')) ? $(el).val() : null;
                Drupal.homeBranch.setId(id);
                // Run sort after plugin instances re-init.
                // $(document).delay(300).trigger('hb-location-finder-sort', locationsList);
              }
            },
            addMarkup: function (context) {
              let id = context.data('hb-id');
              let $markup = $(`
              <div class="hb-location-checkbox-wrapper">
                <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox">
                <label for="hb-location-checkbox-` + id + `">Set as my Home Branch</label>
              </div>
              `);
              $markup.appendTo(context);
              // Save created element in plugin.
              this.element = $markup.find('input');
            },
          });

        });

      // Trigger sort after plugin attach.
      $(document).trigger('hb-location-finder-sort', locationsList);
    },
  });

})(jQuery, Drupal);
