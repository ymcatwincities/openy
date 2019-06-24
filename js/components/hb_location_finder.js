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
        this.id = this.$el.closest('.node--type-branch.node--view-mode-teaser').dataset.hbId;
        if (this.locations[this.id] !== 'undefined') {
          this.checked = this.locations[this.id]['selected'];
          // TODO: ADD class to parent teaser if checked.
          // $('.views-row .node--type-branch').removeClass('hb-selected');
          // $('.views-row .node--type-branch[data-hb-id=' + selectedId + ']').addClass('hb-selected');
        }
      },
      change: function () {
        if (this.checked === true) {
          Drupal.homeBranch.setById(this.id);
        }
        else {
          Drupal.homeBranch.setById(null);
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
      let locationsList = $('.field-prgf-location-finder .locations-list .views-row__wrapper', context);
      locationsList.find('.views-row .node--type-branch').each(function (index) {
        // The locations object available in hb_app.js.
        $(this).append('<hb-loc-finder-checkbox v-bind:locations="locations"></hb-loc-finder-checkbox>');
      });
    },
  });

})(jQuery, Drupal);
