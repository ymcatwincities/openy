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
  // Drupal.behaviors.openy_carnation_mob_menu = {
  //   attach: function (context, settings) {
  //     var sidebar = $('#sidebar');
  //     var $target = $('.top-navs');
  //
  //     sidebar.on('show.bs.collapse', function () {
  //       $target.addClass('menu-in');
  //     });
  //
  //     sidebar.on('hide.bs.collapse', function () {
  //       $target.removeClass('menu-in');
  //     });
  //   }
  // };

  // Dropdown menu height.
  Drupal.behaviors.openyDropdownMenu = {
    attach: function (context, settings) {
      $('.nav-desktop .nav-level-2').each(function (index, element) {
        var item = $(element);
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

  // // Sidebar collapsible.
  // Drupal.behaviors.sidebar = {
  //   attach: function (context, settings) {
  //     var current_scroll = 0;
  //     $('.sidebar')
  //       .once()
  //       .on('show.bs.collapse',
  //         // Add custom class for expand specific styling. in = open.
  //         function (e) {
  //           // Header banner.
  //           $('.banner-zone-node')
  //             .addBack()
  //             .removeClass('out')
  //             .addClass('collapsing-in');
  //
  //           $(this)
  //             .next('.viewport')
  //             .addBack()
  //             .removeClass('out')
  //             .addClass('collapsing-in');
  //
  //           current_scroll = $(window).scrollTop();
  //           $('.nav-global').css({
  //             top: current_scroll
  //           });
  //           $(window).trigger('resize');
  //         }
  //       )
  //       .on('shown.bs.collapse',
  //         // Allow css to control open rest state.
  //         function () {
  //           // Header banner.
  //           $('.banner-zone-node')
  //             .addBack()
  //             .removeClass('collapsing-in')
  //             .addClass('in');
  //
  //           $(this)
  //             .next('.viewport')
  //             .addBack()
  //             .removeClass('collapsing-in')
  //             .addClass('in');
  //
  //           var body =  $('body');
  //           body.addClass('sidebar-in');
  //
  //           $('html').addClass('sidebar-in');
  //           $(window).trigger('resize');
  //         }
  //       )
  //       .on('hide.bs.collapse',
  //         // Add custom class for collapse specific styling. out = closed.
  //         function (e) {
  //           var sidebar = $(this);
  //
  //           // Header banner.
  //           $('.banner-zone-node')
  //             .addBack()
  //             .removeClass('in')
  //             .addClass('collapsing-out');
  //
  //           sidebar
  //             .next('.viewport')
  //             .addBack()
  //             .removeClass('in')
  //             .addClass('collapsing-out');
  //
  //           $(window).scrollTop(current_scroll);
  //
  //           $('#page-head').css({
  //             marginTop: ''
  //           });
  //           $(window).trigger('resize');
  //         }
  //       )
  //       .on('hidden.bs.collapse',
  //         // Allow css to control closed rest state.
  //         function () {
  //           // Header banner.
  //           $('.banner-zone-node')
  //             .addBack()
  //             .addClass('out')
  //             .removeClass('collapsing-out');
  //
  //           $(this)
  //             .next('.viewport')
  //             .addBack()
  //             .addClass('out')
  //             .removeClass('collapsing-out');
  //
  //           $('body').removeClass('sidebar-in');
  //           $('html').removeClass('sidebar-in');
  //
  //           $('.nav-global').css({
  //             top: 0
  //           });
  //           $(window).trigger('resize');
  //         }
  //       )
  //       .find('li')
  //       .on('hide.bs.dropdown',
  //         // For nested dropdowns, prevent collapse of other dropdowns.
  //         function (e) {
  //           e.preventDefault();
  //         }
  //       );
  //   }
  // };

  // Sidebar collapsible menu items.
  // Drupal.behaviors.sidebarMenuCollapsible = {
  //   attach: function (context, settings) {
  //     $('.sidebar .dropdown-toggle').on('click', function () {
  //       var expanded = $(this).attr('aria-expanded');
  //       if (expanded === 'true') {
  //         $(this).removeAttr('aria-expanded');
  //         $(this).parent().removeClass('open');
  //         return false;
  //       }
  //     });
  //   }
  // };

  // Re-size.
  $(window).resize(function () {
    Drupal.behaviors.openyDropdownMenu.attach();
  });

  // // Sidebar collapsible.
  // Drupal.behaviors.openy_carnation_init = {
  //   attach: function (context, settings) {
  //
  //     $('.webform-submission-form').addClass('container');
  //
  //     if($(".field-link-attribute:contains('New Window')").length) {
  //       $('.field-prgf-clm-link a').attr('target', '_blank');
  //     }
  //
  //   }
  // };

})(jQuery);
