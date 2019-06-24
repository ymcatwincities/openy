/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  // TODO: Delete this.
  Vue.config.devtools = true;

  /**
   * Init home branch extension.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranch = {
    attach(context, settings) {
      $('body', context).once('home-branch-app').each(function () {
        Drupal.homeBranch.addMarkup(context);
        Drupal.homeBranch.initVueApp(context);
      });
    }
  };

  Drupal.homeBranch.initVueApp = function (context) {
    new Vue({
      el: '.layout-container',
      Drupal: Drupal,
      data: {
        locations: {
          16: { id: 16, title: 'Brandywine Branch', selected: false },
          21: { id: 21, title: 'Jennersville Branch', selected: false },
          26: { id: 26, title: 'Kennett Branch', selected: false },
          31: { id: 31, title: 'Octorara Program Center', selected: false },
          36: { id: 36, title: 'Oscar Lasko Branch', selected: false },
          641396: { id: 641396, title: 'Outdoor Pool at Kennett Area YMCA', selected: false },
          41: { id: 41, title: 'Lionville Branch', selected: false },
          46: { id: 46, title: 'Upper Main Line Branch', selected: false },
          61: { id: 61, title: 'West Chester Branch', selected: false }
        },
      },
      mounted(){
        // TODO: get locations from API.
        this.init();
        // Subscribe on jQuery event inside Vue App.
        $(document).on('hb-after-storage-update', this.init);
      },
      methods: {
        init: function () {
          let selectedId = Drupal.homeBranch.getValue('id');
          if (selectedId) {
            for (let id in this.locations) {
              if (this.locations.hasOwnProperty(id)) {
                let originData = this.locations[id];
                originData.selected = (originData['id'] == selectedId);
                this.$set(this.locations, id, originData);
              }
            }
          }
        },
      },
    });
  };

  /**
   * Add markup for all components on the page.
   */
  Drupal.homeBranch.addMarkup = function (context) {
    if (typeof Drupal.homeBranch.componentsMarkup !== 'undefined' && Drupal.homeBranch.componentsMarkup.length > 0) {
      Drupal.homeBranch.componentsMarkup.forEach(function (component, key, arr, context) {
        component.getMarkup(context);
      });
    }
  };

})(jQuery, Drupal);
