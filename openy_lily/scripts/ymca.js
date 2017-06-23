(function ($, Drupal, drupalSettings) {
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
      $(".blog-heading", context).each(function () {
        $('.blog-heading').matchHeight();
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
        });
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
   * Scroll to current failed validation field.
   */
  Drupal.behaviors.scrollToError = {
    attach: function (context, settings) {
      $('form', context).each(function () {
        var delay = 200;
        var offset = 180;
        document.addEventListener('invalid', function (e) {
          $(e.target).addClass("invalid");
          $('html, body').animate({scrollTop: $($(".invalid")[0]).offset().top - offset}, delay);
        }, true);
        document.addEventListener('change', function (e) {
          $(e.target).removeClass("invalid");
        }, true);
      });
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

})(jQuery, Drupal, drupalSettings);
