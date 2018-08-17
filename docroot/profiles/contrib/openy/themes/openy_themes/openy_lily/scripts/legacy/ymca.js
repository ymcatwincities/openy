(function ($) {
  var ymca_theme_semaphore = false;

  Drupal.behaviors.ymca_theme = {
    attach: function (context, settings) {

      function getUrlVars() {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
          vars[key] = value;
        });
        return vars;
      }

      if (!ymca_theme_semaphore) {
        ymca_theme_semaphore = true;
        var url_vars = getUrlVars(),
          blog_archive_active = false,
          blog_archive_month,
          blog_archive_year;

        if (typeof url_vars.month != "undefined") {
          blog_archive_month = url_vars.month;
          blog_archive_year = url_vars.year;
        }
        else {
          var path_split = window.location.pathname.split("/");
          blog_archive_month = path_split[path_split.length - 2];
          blog_archive_year = path_split[path_split.length - 3];
        }

        var component_el = $(".abe_blog_archives_display");

        var blog_current_year_el = component_el.find("#blog_archive_year_" + blog_archive_year);
        blog_current_year_el.css({"display": "block"});
        blog_current_year_el.prevAll('.blog_year_li').addClass("expanded")
          .find('.blog_year_link > i').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
        component_el.find("#blog_archive_month_" + blog_archive_year + "_" + blog_archive_month).addClass("active");

        component_el.find('.blog_year_link').on('click', function (e) {
          e.preventDefault();
          var el = $(this);
          var year_li = el.parents('.blog_year_li');
          year_li.nextAll('.blog_months_container_li').first().slideToggle('fast');
          if (year_li.hasClass("expanded")) {
            year_li.find('.blog_year_link > i').removeClass('glyphicon-minus-sign').addClass('glyphicon-plus-sign');
            year_li.removeClass("expanded");
          }
          else {
            year_li.find('.blog_year_link > i').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
            year_li.addClass("expanded");
          }
        });

        // Youth Sports page
        $('.path-youth-sports .join-the-y').on('click touchend', function (e) {
          e.preventDefault();
          var top = $('.content-cards').offset().top;
          $('html, body').animate({scrollTop: top}, 1000);
          return false;
        });

        $('.path-youth-sports .scroll-to-the-video').on('click touchend', function (e) {
          e.preventDefault();
          var top = $('.video-container').offset().top;
          $('html, body').animate({scrollTop: top}, 1000);
          return false;
        });

        // 2014 Annual Report pages
        $(".page_2014_annual_report a[data-toggle='collapse']").click(function () {
          if ($(this).text() == 'Read more') {
            $(this).addClass('opened');
          } else {
            $(this).text('Read more');
          }
        });
      }
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
      $(".sidebar-toggle", context).each(function () {
        $(this).on('click', function (event) {
          $('.viewport, .navbar-toggler').toggleClass('expanded-mobile');
        });
      });
    }
  };

  /**
   * Mobile UX.
   */
  Drupal.behaviors.mobile_ux_sidebar_menu = {
    attach: function (context, settings) {
      if (typeof(settings.ymca_menu) !== 'undefined' && settings.ymca_menu.show_mobile_submenu) {
        $('body').addClass('show-mobile-sub-navigation');
        // Basic case, only sidebar presents.
        if ($('.field-prgf-sidebar-menu-ref').length === 1 && $('.mobile-subnav').length === 0) {
          $('<div class="mobile-subnav" />').insertAfter('#small-text-banner');
          $('.field-prgf-sidebar-menu-ref').clone(true).appendTo('.mobile-subnav');
          $('.mobile-subnav .panel-heading a').append('<b class="caret"></b>').click(function (e) {
            e.preventDefault();
            if ($(this).hasClass('open')) {
              $(this).removeClass('open').parents('.field-prgf-sidebar-menu-ref').find('.panel-body').slideUp();
            }
            else {
              $(this).addClass('open').parents('.field-prgf-sidebar-menu-ref').find('.panel-body').slideDown();
            }
          });
        }
        // Extended case, merge sidebar and navbar.
        if ($('.nav-location').length === 1) {
          $('.nav-location').removeClass('hidden-xs');
          if ($('.field-prgf-sidebar-menu-ref').length === 1) {
            $('.field-prgf-sidebar-menu-ref ul.nav:eq(0)').clone(true).appendTo('.nav-location .nav .current');
          }
          if ($('.nav-location a.home .name').length === 0) {
            var home = $('.nav-location a.home'),
              brand = $('.masthead-brand'),
              name = brand.text(),
              href = home.attr('href');
            brand.wrapInner('<a href="' + href + '"></a>');
            home.append('<span class="name">' + Drupal.t('Helpful links, info, etc.') + '</span><b class="caret"></b>');
            home.click(function(e) {
              if ($(window).width() < 768) {
                e.preventDefault();
                if ($(this).hasClass('open')) {
                  $(this).removeClass('open').parents('.nav').find('li:not(.heading)').slideUp();
                }
                else {
                  $(this).parents('.nav').find('li:eq(0)').addClass('heading');
                  $(this).addClass('open').parents('.nav').find('li').slideDown();
                }
              }
            });
          }
        }
      }
      $('.sidebar .search-icon', context).on('click', function(e) {
        e.preventDefault();
        if ($(this).hasClass('form-shown')) {
          $('.site-search-sidebar').slideUp();
          $(this).removeClass('form-shown');
        }
        else {
          $('.site-search-sidebar').slideDown();
          $(this).addClass('form-shown');
        }
      });
      $('.sidebar')
        .on('DOMSubtreeModified',
          function (e) {
            var more = $(this).find('li.more a');
            if (!more.hasClass('expand')) {
              more.addClass('expand').append('<span>...</span>').parent().nextAll().hide();
            }
            more.on('click', function(e) {
              e.preventDefault();
              more.hide().parent().nextAll().show();
              $(this).hide();
            })
          }
        );
    }
  };

  /**
   * CDN contact form radios.
   */
  Drupal.behaviors.cdn_contact_form = {
    attach: function (context, settings) {
      $('.cdn-contact-form .form-radio', context).each(function() {
        if ($(this).val() == 0) {
          $(this).next().addClass('no');
        }
        $(this).on('change', function() {
          if ($(this).prop('checked') === true) {
            $(this).parents('.js-webform-radios').find('.checked').removeClass('checked');
            $(this).parent().addClass('checked');
          }
        });
      });
    }
  };

})(jQuery);
