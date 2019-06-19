/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  Vue.config.devtools = true;

  /**
   * Init home branch location finder extension.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchLocationFinder = {
    attach(context, settings) {
      // TODO: check that in all Open Y themes location-finder has same classes.
      let locationsList = $('.field-prgf-location-finder .locations-list .views-row__wrapper', context);

      locationsList.once('home-branch-location-finder').each(function () {
        // TODO: move markup creating to another function that can be overrided
        //       later. See example on tabledrag in drupal.
        // Add markup for vue app.
        locationsList.find('.views-row .node--type-branch').each(function (index) {
          let id = $(this).attr('data-hb-id');
          $(this).append('<div class="hb-location hb-location-' + id + '">' +
            '<input type="checkbox" id="checkbox" value="' + id + '" name="hb-location-' + id + '" v-model="locations" @change="onChange(' + id + ')">' +
            '<label for="hb-location-' + id + '" v-text="getLabel(' + id + ')"></label></div>');
        });

        // TODO: move vue instance creating to another function that
        //       can be overrided later. See example on tabledrag in drupal.
        // Init Vue app on top of location finder.
        new Vue({
          el: '.locations-list .views-row__wrapper',
          Drupal: Drupal,
          data: {
            locations: [],
          },
          mounted(){
            this.init();
            // Subscribe on jQuery event inside Vue App.
            $(document).on('hb-after-storage-update', this.init);
          },
          methods: {
            init: function () {
              console.log('INIT');
              let selectedId = this.getSelectedHomeBranch();
              if (selectedId) {
                this.locations = [selectedId];
                $('.views-row .node--type-branch').removeClass('hb-selected');
                $('.views-row .node--type-branch[data-hb-id=' + selectedId + ']').addClass('hb-selected');
              }
            },
            getLabel: function (id) {
              return (this.locations.includes(id)) ? 'My Home Branch' : 'Set as my Home Branch';
            },
            getSelectedHomeBranch: function () {
              return Drupal.homeBranch.getValue('id');
            },
            checked: function (id) {
              // TODO: check case when ID is null.
              return this.locations.includes(id.toString());
            },
            onChange: function (id) {
              if (this.checked(id)) {
                Drupal.homeBranch.setById(id);
              }
              else {
                Drupal.homeBranch.setById(null);
              }
            },
          },
        });
      });
    }
  }

})(jQuery, Drupal);
