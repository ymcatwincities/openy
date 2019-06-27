/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Add global component for hb_app.
   */
  Vue.component('hb-loc-finder-checkbox', {
    props: ['locations'],
    data: function () {
      return { id: null, checked: false }
    },
    watch: {
      locations: {
        handler(val) { this.init(); },
        deep: true
      }
    },
    mounted(){
      this.init();
    },
    methods: {
      init: function () {
        let branchEl = this.$el.closest('.node--type-branch.node--view-mode-teaser');
        this.id = branchEl.dataset.hbId;
        if (this.locations.hasOwnProperty(this.id) && this.locations[this.id].hasOwnProperty('selected')) {
          this.checked = this.locations[this.id]['selected'];
          if (this.checked) {
            branchEl.classList.add('hb-selected');
          }
          else {
            branchEl.classList.remove('hb-selected');
          }
          // TODO: move selected item to the first place (use css or js).
        }
      },
      change: function () {
        if (this.checked === true) {
          Drupal.homeBranch.setId(this.id);
        }
        else {
          Drupal.homeBranch.setId(null);
        }
      },
      getLabel: function () {
        return (this.checked) ? 'My Home Branch' : 'Set as my Home Branch';
      },
    },
    template: `<div class="hb-location-checkbox-wrapper">
      <input
        type="checkbox"
        v-bind:value="id"
        v-on:change="change"
        v-bind:name="'hb-location-' + id"
        v-model="checked"
      >
      <label v-bind:for="'hb-location-' + id" v-text="getLabel()"></label></div>`
  });

  /**
   * Add markup, that required for this component.
   */
  Drupal.homeBranch.componentsMarkup.push({
    getMarkup: (context) => {
      // TODO: move selectors to constants.
      let locationsList = $('.field-prgf-location-finder .locations-list .views-row__wrapper', context);
      locationsList.find('.views-row .node--type-branch').each(function (index) {
        // The locations object available in hb_app.js.
        $(this).append('<hb-loc-finder-checkbox v-bind:locations="locations"></hb-loc-finder-checkbox>');
      });
    },
  });

})(jQuery, Drupal);
