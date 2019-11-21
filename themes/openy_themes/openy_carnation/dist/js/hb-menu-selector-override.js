/**
 * @file
 * Carnation overrides Home branch menu selector plugin.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Override hb-menu-selector.js
   */
  if (Drupal.homeBranch.plugins.length > 0) {
    for (var key in Drupal.homeBranch.plugins) {
      // Find `hb-menu-selector` in home branch plugins list.
      if (Drupal.homeBranch.plugins.hasOwnProperty(key) && Drupal.homeBranch.plugins[key].name === 'hb-menu-selector') {
        // Also, we can override component markup.
        // @see openy_home_branch/js/hb-plugins/hb-menu-selector.js
        Drupal.homeBranch.plugins[key].settings.addMarkup = function (context) {
          var desktopMenu = $('.nav-global .page-head__top-menu ul.navbar-nav');
          desktopMenu.prepend('<li class="h-100 d-flex align-items-center"><a class="nav-link px-4 hb-menu-selector" href="#">' + this.defaultTitle + '</a></li>');

          var mobileMenu = $('.sidebar .user-account-menu--mobile');
          mobileMenu.prepend('<li class="nav-item nav-level-2"><a class="nav-link hb-menu-selector" href="#">' + this.defaultTitle + '</a></li>');
          // Save created element in plugin.
          this.element = $(this.selector, desktopMenu).add(this.selector, mobileMenu);
        };
        Drupal.homeBranch.plugins[key].settings.menuSelector = '.nav-global .page-head__top-menu ul.navbar-nav, .sidebar .user-account-menu--mobile';
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
