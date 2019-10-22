/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * Show/hide desktop search block.
   */
  Drupal.behaviors.openySearchToggle = {
    attach: function (context, settings) {
      var searchBtn = $('.site-search button');
      var searchInput = $('header input.search-input');
      var mainMenuLinks = $('.page-head__main-menu .nav-level-1 li:not(:eq(0))').find('a, button');
      var searchClose = $('.page-head__search-close');

      searchBtn.once('openy-search-toggle-hide').on('click', function () {
        mainMenuLinks.removeClass('show').addClass('fade');
        setTimeout(function () {
          searchInput.focus();
        }, 500);
      });

      searchClose.once('openy-search-toggle-show').on('click', function () {
        mainMenuLinks.addClass('show');
      });
    }
  };

})(jQuery);
