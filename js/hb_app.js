/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

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
      // TODO: move selectors to constants.
      el: '.layout-container',
      Drupal: Drupal,
      data: {
        locations: {},
      },
      mounted() {
        this.getLocations();
        // Subscribe on jQuery event inside Vue App.
        $(document).on('hb-after-storage-update', this.init);
      },
      methods: {
        init: function () {
          let selectedId = Drupal.homeBranch.getValue('id');
          for (let id in this.locations) {
            if (this.locations.hasOwnProperty(id)) {
              let originData = this.locations[id];
              originData.selected = originData['id'] == selectedId;
              this.$set(this.locations, id, originData);
            }
          }
        },
        getLocations: function () {
          let url = drupalSettings.path.baseUrl + 'api/home-branch/locations';
          this.locations = {};
          self = this;
          $.getJSON(url, function (data) {
            data.forEach(function (item) {
              let data = { id: item.nid, title: item.title, selected: false };
              self.$set(self.locations, item.nid, data);
            });
            self.init();
          });
        }
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

})(jQuery, Drupal, drupalSettings);
