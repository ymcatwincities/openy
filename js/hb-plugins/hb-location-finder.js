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
      // TODO: Other handlers detached after sort. so plugin init doesn't work.
      // TODO: Find other solution.
      // $(document).unbind().on('hb-location-finder-sort', function (event, el) {
      //   let items = $(el).find('.views-row');
      //   items.sort(function (a, b) {
      //     return parseInt($(a).data('hb-sort')) > parseInt($(b).data('hb-sort'));
      //   });
      //
      //   items.bind().appendTo('.field-prgf-location-finder .views-row__wrapper');
      // });
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
      locationsList.find('.views-row .node--type-branch').each(function (index) {
        let id = $(this).attr('data-hb-id');
        let el = $(this);
        // Add sort attribute with default index for correct order on change.
        el.closest('.views-row').attr('data-hb-sort', index);
        el.closest('.views-row').attr('data-hb-sort-origin', index);
        // Attach plugin instance to each location teaser.
        // @see openy_home_branch/js/hb-plugin-base.js
        $(this).hbPlugin({
          selector: '.hb-location-checkbox',
          event: 'change',
          element: null,
          init: function () {
            if (!this.element) {
              return;
            }
            let selected = Drupal.homeBranch.getValue('id');
            let branchEl = this.element.closest('.node--type-branch.node--view-mode-teaser');
            if ($(this.element).val() === selected) {
              this.element.prop('checked', true);
              branchEl.addClass('hb-selected');
              branchEl.find('.hb-location-checkbox-wrapper label').text('My Home Branch');
              branchEl.closest('.views-row').attr('data-hb-sort', -1);
            }
            else {
              this.element.prop('checked', false);
              branchEl.removeClass('hb-selected');
              branchEl.find('.hb-location-checkbox-wrapper label').text('Set as my Home Branch');
              branchEl.closest('.views-row').attr('data-hb-sort', branchEl.closest('.views-row').attr('data-hb-sort-origin'));
            }
          },
          onChange: function (event, el) {
            // Save selected value in сookies storage.
            let id = ($(el).is(':checked')) ? $(el).val() : null;
            Drupal.homeBranch.setId(id);
            // Run sort after plugin instances re-init.
            $(document).delay(300).trigger('hb-location-finder-sort', locationsList);
          },
          addMarkup: function (context) {
            el.append(`
            <div class="hb-location-checkbox-wrapper">
              <input type="checkbox" value="` + id + `" id="hb-location-checkbox-` + id + `" class="hb-location-checkbox">
              <label>Set as my Home Branch</label>
            </div>
            `);
            // Save created element in plugin.
            this.element = $('#hb-location-checkbox-' + id);
          },
        });

      });

      // Trigger sort after plugin attach.
      $(document).trigger('hb-location-finder-sort', locationsList);
    },
  });

})(jQuery, Drupal);
