/**
 * @file
 * OpenY Carnation JS.
 */

(function ($) {
  "use strict";
  Drupal.openy_carnation = Drupal.openy_carnation || {};

  //  Move Header Banner paragraph to header.
  Drupal.behaviors.openy_carnation_banner_node = {
    attach: function (context, settings) {
      var banner_header = $('.paragraph--type--banner');
      if (banner_header.length > 0) {
        $('.banner-zone-node').once('move').append(banner_header.eq(0));
        $('body').addClass('with-banner');
      } else {
        $('body').addClass('without-banner');
      }
    }
  };

  // Show/hide desktop search block.
  Drupal.behaviors.openy_carnation_search_md = {
    attach: function (context, settings) {
      var search_md = $('.site-search button');
      var main_menu_links_md = $('.page-head__main-menu .nav-level-1 li:not(:eq(0))').find('a, button');
      var search_close_md = $('.page-head__search-close');

      search_md.once('search-toggle-hide').on('click', function () {
        main_menu_links_md.removeClass('show').addClass('fade');
      });

      search_close_md.once('search-toggle-show').on('click', function () {
        main_menu_links_md.addClass('show');
      });
    }
  };

  // Add class to header when mobile menu is opened.
  Drupal.behaviors.openy_carnation_mob_menu = {
    attach: function (context, settings) {
      var sidebar = $('#sidebar');
      var $target = $('.top-navs');

      sidebar.on('show.bs.collapse', function () {
        $target.addClass('menu-in');
      });

      sidebar.on('hide.bs.collapse', function () {
        $target.removeClass('menu-in');
      });
    }
  };

  // Dropdown menu height.
  Drupal.behaviors.openyDropdownMenu = {
    attach: function (context, settings) {
      $('.page-head__main-menu .nav-level-2').each(function (index, item) {
        var item = $(item);
        var offset = item.offset();
        var bottom = offset.top + item.height() - $(window).scrollTop();
        var maxHeight = $(window).height() - bottom - 15;
        item.find('.dropdown-menu').eq(0).css('max-height', maxHeight);
      });
    }
  };

  // BS4 data-spy: affix replacement
  Drupal.behaviors.openy_carnation_header_affix = {
    attach: function (context, settings) {
      $(window).on('scroll', function(event) {
        var scrollValue = $(window).scrollTop();
        if (scrollValue === settings.scrollTopPx || scrollValue > 1) {
          $('.top-navs').addClass('affix');
        } else if (scrollValue === settings.scrollTopPx || scrollValue < 1) {
          $('.top-navs').removeClass('affix');
        }
      });
    }
  };

  // Sidebar collapsible.
  Drupal.behaviors.openy_carnation_init = {
    attach: function (context, settings) {

      $('.webform-submission-form').addClass('container');

      if($(".field-link-attribute:contains('New Window')").length) {
        $('.field-prgf-clm-link a').attr('target', '_blank');
      }

    }
  };

})(jQuery);
