(function ($) {
  "use strict";
  Drupal.ygh =  Drupal.ygh || {};

  Drupal.behaviors.ygh = {
    attach: function (context, settings) {
      $('.ui-tabs').tabs({
        active: false,
        collapsible: true
      });

      $(window).load(function () {

      });
      $(window).resize(function () {
        Drupal.ygh.branch__updates_queue_mobile();
        Drupal.ygh.paragraph_4c();
      });

      Drupal.ygh.branch__updates_queue_mobile();
      Drupal.ygh.paragraph_4c();
      Drupal.ygh.join_form();

      // Set active language text.
      var active_lang = $('.page-head__language-switcher .is-active a:eq(0)').text();
      $('.page-head__language-switcher .lang').text(active_lang);
      $('.top-navs__language-switcher .lang').text(active_lang);
    }
  };

  Drupal.ygh.paragraph_4c = function () {
    $('.block-description--4').each(function () {
      var view = $(this);

      // Initialize Slick.
      if (!view.find('.wrapper').hasClass('slick-initialized')) {
        if ($(window).width() < 768) {
          view.find('.wrapper').slick({
            responsive: [
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
                  infinite: false,
                  speed: 300,
                  arrows: true,
                  dots: true,
                  prevArrow: '<i class="slick-prev fa fa-chevron-left"></i>',
                  nextArrow: '<i class="slick-next fa fa-chevron-right"></i>'
                }
              }]
          });
        }
      }
      // Slick is initialised but we check if it fits screen size.
      if ($(window).width() >= 768 && view.find('.wrapper').hasClass('slick-initialized')) {
        view.find('.wrapper').slick('unslick');
      }
    });
  };

  Drupal.ygh.branch__updates_queue_mobile = function () {
    $('.branch--updates-queue').each(function () {
      var view = $(this);

      // Initialize Slick.
      if (!view.find('> div').hasClass('slick-initialized')) {
        if ($(window).width() < 768) {
          view.find('> div').slick({
            responsive: [
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
                  infinite: true,
                  speed: 300,
                  arrows: true,
                  dots: true,
                  prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
                  nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>'
                }
              }]
          });
        }
      }
      // Slick is initialised but we check if it fits screen size.
      else if ($(window).width() >= 768 && view.find('> div').hasClass('slick-initialized')) {
        view.find('> div').slick('unslick');
      }
    });
  };

  // Sidebar collapsible.
  Drupal.behaviors.sidebar = {
    attach: function (context, settings) {
      var current_scroll = 0;
      $('.sidebar')
        .once()
        .on('show.bs.collapse',
          // Add custom class for expand specific styling. in = open.
          function (e) {
            $(this)
              .next('.viewport')
              .addBack()
              .removeClass('out')
              .addClass('collapsing-in');

            current_scroll = $(window).scrollTop();
            $('.nav-global').css({
              top: current_scroll
            });
          }
        )
        .on('shown.bs.collapse',
          // Allow css to control open rest state.
          function () {
            $(this)
              .next('.viewport')
              .addBack()
              .removeClass('collapsing-in')
              .addClass('in');

            var body =  $('body');

            body.addClass('sidebar-in');

            $('html').addClass('sidebar-in');
          }
        )
        .on('hide.bs.collapse',
          // Add custom class for collapse specific styling. out = closed.
          function (e) {
            var sidebar = $(this);
            sidebar
              .next('.viewport')
              .addBack()
              .removeClass('in')
              .addClass('collapsing-out');

            $(window).scrollTop(current_scroll);

            $('#page-head').css({
              marginTop: ''
            });

          }
        )
        .on('hidden.bs.collapse',
          // Allow css to control closed rest state.
          function () {
            $(this)
              .next('.viewport')
              .addBack()
              .addClass('out')
              .removeClass('collapsing-out');

            $('body').removeClass('sidebar-in');
            $('html').removeClass('sidebar-in');

            $('.nav-global').css({
              top: 0
            });
          }
        )
        .find('li')
        .on('hide.bs.dropdown',
          // For nested dropdowns, prevent collapse of other dropdowns.
          function (e) {
            e.preventDefault();
          }
        );
    }
  };

  // Sidebar collapsible menu items.
  Drupal.behaviors.sidebarMenuCollapsible = {
    attach: function (context, settings) {
      $('.sidebar .dropdown-toggle').on('click', function () {
        var expanded = $(this).attr('aria-expanded');
        if (expanded == 'true') {
          $(this).removeAttr('aria-expanded');
          $(this).parent().removeClass('open');
          return false;
        }
      });
    }
  };

  // Join form handler.
  Drupal.ygh.join_form = function () {
    $('.js-form-type-membership-type-radio').each(function() {
      var element = $(this);
      element.find('.btn').on('click', function() {
        element.find('input').attr('checked', true);
        $(this).parents('form').find('.js-form-submit').trigger('mousedown');
      });
    });
  };

  // Membership calculator.
  Drupal.behaviors.calc = {
    attach: function (context, settings) {
      if ($('#membership-calc-wrapper').length > 0) {
        $(document).ajaxSuccess(function () {
          $('html, body').animate({
            scrollTop: $("#membership-calc-wrapper").offset().top
          }, 200);
        });
      }
    }
  };

  // Whats happening at the Y.
  Drupal.behaviors.whatw = {
    attach: function (context, settings) {
      if ($('.field-prgf-whay-title').length > 0 && $('.field-prgf-whay-title.mobile').length === 0) {
        $('.field-prgf-whay-title')
          .addClass('hidden-xs')
          .clone()
          .addClass('mobile')
          .addClass('hidden-sm')
          .removeClass('hidden-xs')
          .prependTo('.wrapper-field-branch-body');
      }
    }
  };

})(jQuery);
