/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * Add class to header when mobile menu is opened.
   */
  Drupal.behaviors.openyMobileMenu = {
    attach: function (context, settings) {
      var sidebar = $('#sidebar');
      var topNav = $('.top-navs');

      sidebar.on('show.bs.collapse', function () {
        topNav.addClass('menu-in');
      });

      sidebar.on('hide.bs.collapse', function () {
        topNav.removeClass('menu-in');
      });

      $(window).resize(function() {
        if($(window).width() > 991) {
          topNav.removeClass('menu-in');
          sidebar.collapse('hide');
        }
      });
    }
  };

  /**
   * Mobile UX for Microsites menu.
   */
  Drupal.behaviors.mobile_microsites_menu = {
    attach: function (context, settings) {
      if ($(window).width() > 992) {
        return;
      }
      var menu = $('.microsites-menu__wrapper');
      if (menu.length === 0) {
        menu = $('.paragraph--type--camp-menu');
      }

      if (menu.length === 0) {
        return;
      }
      if ($('ul li a', menu).length === 0) {
        return;
      }
      $('ul li', menu).css('display', 'none');
      var home = $('ul li a', menu).first();
      home.text('');
      home.append('<span class="name">' + Drupal.t('Helpful links, info, etc.') + '</span><b class="caret"></b>');
      home.parent().css('display', 'list-item');
      home.click(function (e) {
        e.preventDefault();
        if ($(this).hasClass('open')) {
          $(this).removeClass('open').parents('ul.camp-menu').find('li:not(.heading)').slideUp();
        }
        else {
          $(this).parents('ul.camp-menu').find('li:eq(0)').addClass('heading');
          $(this).addClass('open').parents('ul.camp-menu').find('li').slideDown();
        }
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var menu = $('.microsites-menu__wrapper');
        if (menu.length === 0) {
          menu = $('.paragraph--type--camp-menu');
        }
        var home = $('ul li a', menu).first();
        home.unbind('click');
        home.html(Drupal.t('Home'));
        $('ul li', menu).css('display', 'table-cell');
      }
    }
  };

  /**
   * Mobile UX.
   */
  Drupal.behaviors.mobile_ux = {
    attach: function (context, settings) {
      $(window).on('orientationchange', function () {
        Drupal.behaviors.mobile_microsites_menu.detach(context, settings, 'unload');
        Drupal.behaviors.mobile_microsites_menu.attach(context, settings);
      });
    }
  };
})(jQuery);
