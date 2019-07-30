/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Add plugin, that related to Location Finder.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-menu-selector',
    attach: (context) => {
      // Attach plugin instance to header menu item.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.hb-menu-selector', context).hbPlugin({
        selector: 'a.hb-menu-selector',
        event: 'click',
        element: null,
        init: function () {
          console.log('INIT');
          if (!this.element) {
            return;
          }
          // TODO: investigate why on first load selected not detected.
          let selected = Drupal.homeBranch.getValue('id');
          let locations = Drupal.homeBranch.getLocations();
          if (selected) {
            this.element.text(locations[selected]);
          }
          else {
            this.element.text('My home branch');
          }
        },
        onChange: function (event, el) {
          // Show HB locations modal.
          Drupal.homeBranch.showModal();
        },
        addMarkup: function (context) {
          // TODO: move selectors to constants.
          let topMenu = $('.nav-global .page-head__top-menu ul.navbar-nav', context);
          topMenu.prepend('<li><a class="hb-menu-selector" href="#">My home branch</a></li>');
          // Save created element in plugin.
          this.element = $('.hb-menu-selector');
        },
      });
    },
  });

})(jQuery, Drupal);
