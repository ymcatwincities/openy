/**
 * @file
 * Theme javascrip logic.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  // It closes the ui dialog on an outside click.
  if (typeof drupalSettings.dialog != 'undefined') {
    drupalSettings.dialog.open = function (event) {
      $('.ui-widget-overlay').on('click', function () {
        $(event.target).dialog('close');
      });
    };
  }

  /**
   * Cliendside Email validation.
   */
  Drupal.behaviors.ymca_email_pattern = {
    attach: function (context, settings) {
      $("input[type=email]", context).each(function () {
        if (!$(this).attr('pattern')) {
          $(this).attr('pattern', '[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+\\.(?:[a-zA-Z0-9-\\.]+)*');
        }
      });
    }
  };

  /**
   * Resize header on scroll.
   */
  Drupal.behaviors.resizeHeader = {
    attach: function (context, settings) {
      $("#page-head", context).each(function () {
        $(window).on("scroll touchmove", function () {
          $('#page-head').toggleClass('tiny', $(document).scrollTop() > 0);
          $('body').toggleClass('tiny-header', $(document).scrollTop() > 0);
        });
      });
    }
  };

  /**
   * Match Height on article boxes.
   */
  Drupal.behaviors.matchHeight = {
    attach: function (context, settings) {
      $(".news-more-teaser, .blog-more-teaser", context).each(function () {
        $('.blog-up').matchHeight();
        $('.blog-heading').matchHeight();
        $('.inner-wrapper').matchHeight();
      });
      $(".featured-highlights", context).each(function () {
        $('.blog-up').matchHeight();
        $('.blog-heading').matchHeight();
      });
      $(document).ajaxComplete(function(event, xhr, settings) {
        $(".news-more-teaser, .blog-more-teaser").each(function () {
          $('.blog-up').matchHeight();
          $('.blog-heading').matchHeight();
          $('.inner-wrapper').matchHeight();
        });
        $(".featured-highlights", context).each(function () {
          $('.blog-up').matchHeight();
          $('.blog-heading').matchHeight();
        });

      });
    }
  };

  /**
   * Search toggle.
   */
  Drupal.behaviors.searchToggle = {
    attach: function (context, settings) {
      $(".search-toggle", context).each(function () {
        $(this).on('click', function (event) {
          $('#search-box').toggleClass('expanded-search');
          $('#page-head').toggleClass('expanded-search');
        });
      });
    }
  };

  /**
   * Main menu toggle.
   */
  Drupal.behaviors.menuToggle = {
    attach: function (context, settings) {
      $('#block-openy-lily-main-menu .dropdown-toggle', context).each(function () {
        var $menuItem = $("#block-openy-lily-main-menu .dropdown-toggle");
        var $container = $("#main");
        $(this).on('click', function (e) {
          $(this).toggleClass('expanded-menu');
          $($menuItem).not($(this)).removeClass('expanded-menu');
          e.preventDefault();
          $($container).removeClass('expanded-menu');
          if ($(this).hasClass('expanded-menu')) {
            $($container).addClass('expanded-menu');
          }
        });
      });
      $(document).mouseup(function (e) {
        var $container = $("#block-openy-lily-main-menu");
        if (!$container.is(e.target) && $container.has(e.target).length === 0) {
          $('#main').removeClass('expanded-menu');
          $('#block-openy-lily-main-menu .dropdown-toggle').removeClass('expanded-menu');
        }
      });
    }
  };

  /**
   * Mobile menu toggle.
   */
  Drupal.behaviors.mobileMenuToggle = {
    attach: function (context, settings) {
      $(".navbar-toggler", context).each(function () {
        $(this).on('click', function (event) {
          $(this).toggleClass('expanded-mobile');
          $('#side-area, .viewport').toggleClass('expanded-mobile');
          if ($('#side-area').hasClass('expanded-mobile')) {
            $('#side-area').removeAttr('aria-hidden');
            $('.viewport').attr('aria-hidden', 'true');
          }
          else {
            $('#side-area').attr('aria-hidden', 'true');
            $('.viewport').removeAttr('aria-hidden');
          }
        });
      });

      // Close mobile menu on link click.
      $("#side-area nav a:not(.dropdown-toggle)", context).click(function() {
        $(".navbar-toggler", context).toggleClass('expanded-mobile');
        $('#side-area, .viewport').toggleClass('expanded-mobile');
        $('#side-area').attr('aria-hidden', 'true');
        $('.viewport').removeAttr('aria-hidden');
      });
    }
  };

  /**
   * Main menu toggle.
   */
  Drupal.behaviors.menuMobileToggle = {
    attach: function (context, settings) {
      $('#block-mainnavigation-2 .dropdown-toggle', context).each(function () {
        $(this).on('click', function (e) {
          e.preventDefault();
          $(this).next('.dropdown-menu').toggleClass('open');
        });
      });
    }
  };

  /**
   * Hide menu on big screens.
   */
  Drupal.behaviors.hideMenuDesktop = {
    attach: function (context, settings) {
      $(window).resize(function () {
        if ($(window).width() > 992) {
          $('.navbar-toggler, #side-area, .viewport ', context).removeClass('expanded-mobile');
        }
      });
    }
  };

  /**
   * Dynamic max-height for main menu submenus.
   */
  Drupal.behaviors.openy_lily_main_menu_submenu_height = {
    attach: function (context, settings) {
      var h = $(window).height();
      $('.main-nav .dropdown-menu.row-level-2', context).css('max-height', h - 250 + 'px');
    }
  };

  /**
   * Scroll to next button.
   */
  Drupal.behaviors.scrollToNext = {
    attach: function (context, settings) {
      $(context).find('.calc-block-form').once('calcForm').each(function () {
        $(this).find('.btn-lg.btn').on('click', function () {
          $('html, body').animate({
            scrollTop: $(".form-submit").offset().top
          }, 2000);
        });
      });
    }
  };

  /**
   * Hide/Show membership form.
   */
  Drupal.behaviors.showMember = {
    attach: function (context, settings) {
      $(context).find('#membership-page .webform-submission-form').once('membForm').each(function () {
        $('.try-the-y-toggle').on('click', function (e) {
          e.preventDefault();
          $('.try-the-y-toggle').addClass('active');
          $('.landing-content > .paragraph:nth-child(1), .landing-content > .paragraph:nth-child(3),  article.webform').slideDown('fast');
          $('html, body').animate({
            scrollTop: $("#membership-page .webform form").offset().top - 250
          }, 500);
        });
      });
    }
  };

  /**
   * Trim description on gallery .
   */
  Drupal.behaviors.trimDesc = {
    attach: function (context, settings) {
      $(context).find('.paragraph--type--gallery .field-prgf-description p').once('glrySld').each(function () {
        $(this).text(function(index, currentText) {
          return currentText.substr(0, 175) + '...';
        });
      });
    }
  };

  /**
   * Match Height on classes.
   */
  Drupal.behaviors.matchHeightClass = {
    attach: function (context, settings) {
      $(".paragraph--type--classes-listing", context).each(function () {
        $('.activity-item').matchHeight();
      });
      $(document).ajaxComplete(function(event, xhr, settings) {
        $(".paragraph--type--classes-listing", context).each(function () {
          $('.activity-item').matchHeight();
        });
      });
    }
  };

  /**
   * Views scroll to top ajax command override.
   */
  Drupal.behaviors.scrollOffset = {
    attach: function (context, settings) {
      if (typeof Drupal.AjaxCommands === 'undefined') {
        return;
      }
      Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
        // Scroll to the top of the view. This will allow users
        // to browse newly loaded content after e.g. clicking a pager
        // link.
        var offset = $(response.selector).offset();
        // We can't guarantee that the scrollable object should be
        // the body, as the view could be embedded in something
        // more complex such as a modal popup. Recurse up the DOM
        // and scroll the first element that has a non-zero top.
        var scrollTarget = response.selector;
        while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
          scrollTarget = $(scrollTarget).parent();
        }
        // Only scroll upward.
        if (offset.top - 10 < $(scrollTarget).scrollTop()) {
          $(scrollTarget).animate({scrollTop: (offset.top - 230)}, 500);
        }
      };
    }
  };

  /**
   * Mobile UX for Microsites menu.
   */
  Drupal.behaviors.mobile_microsites_menu = {
    attach: function (context, settings) {
      if (window.screen.availWidth > 992) {
        return;
      }
      var menu = $('.microsites-menu__wrapper', context);
      if (menu.length === 0) {
        menu = $('.paragraph--type--camp-menu', context);
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
      home.closest('li').css('display', 'list-item');
      home.click(function (e) {
        e.preventDefault();
        if ($(this).hasClass('open')) {
          $(this).removeClass('open').parents('ul.camp-menu').find('li:not(.heading)').slideUp();
          $(this).closest('ul').removeClass('mobile-open');
        }
        else {
          $(this).parents('ul.camp-menu').find('li:eq(0)').addClass('heading');
          $(this).addClass('open').parents('ul.camp-menu').find('li').slideDown();
          $(this).closest('ul').addClass('mobile-open');
        }
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var menu = $('.microsites-menu__wrapper', context);
        if (menu.length === 0) {
          menu = $('.paragraph--type--camp-menu', context);
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
      $(window).on('orientationchange resize', function () {
        Drupal.behaviors.mobile_microsites_menu.detach(context, settings, 'unload');
        Drupal.behaviors.mobile_microsites_menu.attach(context, settings);
      });
    }
  };

  // Location collapsible.
  Drupal.behaviors.schedules_location_collapsed = {
    attach: function (context, settings) {
      $('label[for="form-group-location"]').once().on('click', function () {
        let status = $(this).attr('aria-expanded');
        if (status === 'false') {
          $(this).attr('aria-expanded', 'true');
        }
        else {
          $(this).attr('aria-expanded', 'false');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
