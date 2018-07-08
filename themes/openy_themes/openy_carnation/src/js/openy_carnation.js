/**
 * @file
 * OpenY Carnation JS.
 */
(function ($) {

  "use strict";
  Drupal.openyCarnation = Drupal.openyCarnation || {};

  /**
   * Alert Modals
   */
  Drupal.behaviors.openyAlertModals = {
    attach: function (context, settings) {
      var alertModals = $('.alert-modal', context);

      $(window).on('load',function() {
        if (alertModals.length) {
          alertModals.modal('show');
        }
      });
    }
  };

  /**
   * Move Header Banner paragraph to header.
   */
  Drupal.behaviors.openyBanners = {
    attach: function (context, settings) {
      var bannerHeader = $('.paragraph--type--banner, .landing-header, .program-header, .page-heading');
      if (bannerHeader.length > 0) {
        $('.banner-zone-node').once('openy-banners').append(bannerHeader.eq(0));
        $('body').addClass('with-banner');
      } else {
        $('body').addClass('without-banner');
      }
    }
  };

  /**
   * Ensure breadcrumbs are after banners in the DOM
   */
  Drupal.behaviors.moveBreadcrumbs = {
    attach: function (context, settings) {
      var breadCrumbs = $('.breadcrumbs-wrapper', context);
      var bannerCta = $('.banner-zone-node .banner .banner-cta', context);

      if (breadCrumbs.length && bannerCta.length) {
        breadCrumbs.once('openy-breadcrumbs').appendTo(bannerCta);
      }
    }
  };

  /**
   * Ensure header alerts are after breadcrumbs in the DOM
   */
  Drupal.behaviors.moveHeaderAlerts = {
    attach: function (context, settings) {
      var headerAlerts = $('#block-openy-carnation-views-block-alerts-header-alerts', context);
      var subHeaderFilters = $(
        '.sub-header--filters, ' +
        '#schedules-search-form-wrapper, ' +
        'groupex-form-full', context);

      if (headerAlerts.length && subHeaderFilters.length) {
        headerAlerts.once('openy-breadcrumbs').insertBefore(subHeaderFilters);
      }
    }
  };

  /**
   * Show/hide desktop search block.
   */
  Drupal.behaviors.openySearchToggle = {
    attach: function (context, settings) {
      var searchBtn = $('.site-search button');
      var mainMenuLinks = $('.page-head__main-menu .nav-level-1 li:not(:eq(0))').find('a, button');
      var searchClose = $('.page-head__search-close');

      searchBtn.once('openy-search-toggle-hide').on('click', function () {
        mainMenuLinks.removeClass('show').addClass('fade');
      });

      searchClose.once('openy-search-toggle-show').on('click', function () {
        mainMenuLinks.addClass('show');
      });
    }
  };

  /**
   * Add class to header when mobile menu is opened.
   */
  Drupal.behaviors.openyMobileMenu = {
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

  /**
   * Dropdown menu height.
   */
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

  /**
   * BS4 data-spy: affix replacement
   */
  Drupal.behaviors.openyHeaderAffix = {
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

  /**
   * Branch Locations (Download PDF)
   */
  Drupal.behaviors.openyPdfDownload = {
    attach: function (context, settings) {
      var pdfBtnContainer = $('.groupex-pdf-link-container');
      if (pdfBtnContainer.length) {
        pdfBtnContainer.find('a').html('PDF <i class="fas fa-download"></i>');
        pdfBtnContainer.insertAfter('.groupex-form-full .form-submit');
      }
    }
  };

  // Re-size.
  $(window).resize(function () {
    Drupal.behaviors.openyDropdownMenu.attach();
  });

  /**
   * User Login Form
   */
  Drupal.behaviors.openyUserLogin = {
    attach: function (context, settings) {
      $("input[type='password'][data-eye]").each(function (i) {
        var $this = $(this);

        $this.wrap($("<div/>", {
          style: 'position:relative'
        }));

        $this.css({
          paddingRight: 60
        });

        $this.after($("<div/>", {
          html: 'Show',
          class: 'btn btn-primary btn-sm',
          id: 'passeye-toggle-' + i,
          style: 'position:absolute;right:10px;top:50%;transform:translate(0,-50%);padding: 2px 7px;font-size:12px;cursor:pointer;'
        }));

        $this.after($("<input/>", {
          type: 'hidden',
          id: 'passeye-' + i
        }));

        $this.on("keyup paste", function () {
          $("#passeye-" + i).val($(this).val());
        });

        $("#passeye-toggle-" + i).on("click", function () {
          if ($this.hasClass("show")) {
            $this.attr('type', 'password');
            $this.removeClass("show");
            $(this).removeClass("btn-outline-primary");
          } else {
            $this.attr('type', 'text');
            $this.val($("#passeye-" + i).val());
            $this.addClass("show");
            $(this).addClass("btn-outline-primary");
          }
        });

      });
    }
  };

  // PULLED FROM LILY

  /**
   * Match Heights
   */
  Drupal.behaviors.openyMatchHeight = {
    attach: function (context, settings) {
      matchAllHeight();
    }
  };

  function matchAllHeight() {
    var el = [
      '.viewport .page-head__main-menu .nav-level-3 > a',
      '.blog-up',
      '.blog-heading',
      '.inner-wrapper',
      '.card',
      '.card h3',
      '.card .node__content',
      '.block-description--text > h2',
      '.block-description--text > div',
      '.table-class',
    ];

    // make them all equal heights.
    $.each(el, function (index, value) {
      $(value).matchHeight();
    });
  }

  /**
   * Program Carousels
   */
  Drupal.behaviors.openySubCategoryClassesTheme = {
    attach: function (context, settings) {
      $('.sub-category-classes-view').once().each(function() {
        var view = $(this);

        // Initialize Slick.
        view.find('.activity-group-slider').slick({
          dots: true,
          infinite: false,
          speed: 300,
          slidesToShow: 3,
          slidesToScroll: 3,
          prevArrow: '<button type="button" class="slick-prev" value="' + Drupal.t('Previous') + '" title="' + Drupal.t('Previous') + '">' + Drupal.t('Previous') + '<i class="fa fa-chevron-left" aria-hidden="true"></i></button>',
          nextArrow: '<button type="button" class="slick-next" value="' + Drupal.t('Next') + '" title="' + Drupal.t('Next') + '">' + Drupal.t('Next') + '<i class="fa fa-chevron-right" aria-hidden="true"></i></button>',
          customPaging: function(slider, i) {
            return '<button type="button" data-role="none" aria-hidden="true" role="button" tabindex="' + i + '" value="' + Drupal.t('Slide set @i', {'@i': i+1}) + '" title="' + Drupal.t('Slide set @i', {'@i': i+1}) + '">' + (i+1) + '</button>';
          },
          responsive: [
            {
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                dots: true,
                arrows: true
              }
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                dots: true,
                arrows: true
              }
            }
          ]
        });

        // Filters actions.
        view.find('.add-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container, .actions-wrapper').removeClass('hidden-xs');
            view.find('.close-filters').removeClass('hidden');
            view.find('.filters-container').addClass('hidden');
            $(this).addClass('hidden');
        });
        view.find('.close-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container, .actions-wrapper').addClass('hidden-xs');
            view.find('.add-filters').removeClass('hidden');
            view.find('.filters-container').removeClass('hidden');
            $(this).addClass('hidden');
          });

        view.find('.js-form-type-select select')
          .change(function() {
            if ($(window).width() > 767) {
              view.find('.js-form-type-select select').attr('readonly', true);
              view.find('form .form-actions input:eq(0)').trigger('click');
            }
          });

        view.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            view.parents('.filter').remove();
            view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (view.find('.filter').length === 0) {
              view.find('.filters-container').addClass('hidden');
            }
            view.find('.js-form-type-select select').attr('readonly', true);
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
          });

        view.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.filters-container').find('a.remove').each(function() {
              view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            view.find('.js-form-type-select select').attr('readonly', true);
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
          });
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

          $(context).find('.form-radios .btn-lg.btn').removeClass('btn-success');
          $(this).addClass('btn-success');

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
