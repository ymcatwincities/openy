/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Add plugin, that related to Location Finder.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-menu-selector',
    attach: function (settings) {
      // Attach plugin instance to header menu item.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.hb-menu-selector').hbPlugin(settings);
    },
    settings: {
      selector: '.hb-menu-selector',
      event: 'click',
      element: null,
      menuSelector: drupalSettings.home_branch.hb_menu_selector.menuSelector,
      defaultTitle: drupalSettings.home_branch.hb_menu_selector.defaultTitle,
      init: function () {
        if (!this.element) {
          return;
        }
        var selected = Drupal.homeBranch.getValue('id');
        var locations = Drupal.homeBranch.getLocations();
        if (selected) {
          this.element.text(locations[selected]);
        }
        else {
          this.element.text(this.defaultTitle);
        }
      },
      onChange: function (event, el) {
        var selected = Drupal.homeBranch.getValue('id');
        if (!selected) {
          // Show HB locations modal.
          Drupal.homeBranch.showModal(true);
        }
        else {
          // Redirect to branch page.
          location.href = drupalSettings.path.baseUrl + 'node/' + selected;
        }
      },
      addMarkup: function (context) {
        var menu = $(this.menuSelector);
        menu.prepend('<li><a class="hb-menu-selector" href="#">' + this.defaultTitle + '</a></li>');
        // Save created element in plugin.
        this.element = $(this.selector, menu);
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
