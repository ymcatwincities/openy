/**
 * @file
 * Menu selector extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Add global component for hb_app.
   */
  Vue.component('hb-menu-selector', {
    props: ['locations'],
    data: function () {
      return { id: null, name: null, dontAsk: false, showModal: false }
    },
    computed: {
      getName: function () {
        return this.name ? this.name : 'My home branch';
      }
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
        this.dontAsk = Drupal.homeBranch.getValue('dontAsk');
        this.id = null;
        this.name = null;

        for (let id in this.locations) {
          if (this.locations.hasOwnProperty(id) && this.locations[id].hasOwnProperty('selected') && this.locations[id]['selected'] === true) {
            this.id = this.locations[id]['id'];
            this.name = this.locations[id]['title'];
          }
        }
      },
      save: function (val) {
        if (val === true) {
          Drupal.homeBranch.set(this.id, this.dontAsk);
        }
        else {
          Drupal.homeBranch.setValue('dontAsk', this.dontAsk);
        }
        this.showModal = false;
      },
    },
    template: `<div class="hb-menu-selector-wrapper">
      <a v-text="getName" @click="showModal = true"></a>
      <transition name="modal" v-if="showModal">
        <div class="hb-menu-selector__modal" tabindex="-1" role="dialog">
          <div class="modal-content">
            <div class="hb-menu-selector__modal--header">
              <h4><strong>Home branch</strong></h4>
              <button type="button" class="close" @click="showModal = false"><i class="fa fa-times" aria-hidden="true"></i></button>
            </div>
            <div class="hb-menu-selector__modal--body">
              <div>Would you like to set a different location as your 'home branch'?<br><a>Learn more</a></div>
              <div class="form">
                <select v-model="id" required>
                  <option :value="null" disabled selected>Select location</option>
                  <option v-for="location in locations" v-bind:value="location.id">
                    {{ location.title }}
                  </option>
                </select>
                <div class="dont-ask">
                  <input type="checkbox" id="dont-ask-checkbox" v-model="dontAsk">
                  <label for="dont-ask-checkbox">Don't ask me again</label>
                </div>
              </div>
            </div>
            <div class="hb-menu-selector__modal--footer">
              <button class="btn btn-lg btn-secondary" @click="save(false)">No</button>
              <button class="btn btn-lg btn-secondary" @click="save(true)" :disabled="id == null">Yes</button>
            </div>
          </div>
        </div>
      </transition>
    </div>`
  });

  /**
   * Add markup, that required for this component.
   */
  Drupal.homeBranch.componentsMarkup.push({
    getMarkup: (context) => {
      // TODO: move selectors to constants.
      let topMenu = $('.nav-global .page-head__top-menu ul.navbar-nav', context);
      topMenu.prepend('<li><hb-menu-selector v-bind:locations="locations"></hb-menu-selector></li>');
    },
  });

})(jQuery, Drupal);
