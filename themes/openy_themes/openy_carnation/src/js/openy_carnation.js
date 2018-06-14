/**
 * @file
 * OpenY Carnation JS.
 */

(function ($) {
  "use strict";
  Drupal.openy_carnation = Drupal.openy_carnation || {};

  Drupal.behaviors.openyAlertModals = {
    attach: function (context, settings) {
      var alert_modals = $('.alert-modal', context);

      $(window).on('load',function() {
        if (alert_modals.length) {
          alert_modals.modal('show');
        }
      });
    }
  };

  //  Move Header Banner paragraph to header.
  Drupal.behaviors.openyBanners = {
    attach: function (context, settings) {
      var banner_header = $('.paragraph--type--banner, .landing-header, .program-header');
      if (banner_header.length > 0) {
        $('.banner-zone-node').once('openy-banners').append(banner_header.eq(0));
        $('body').addClass('with-banner');
      } else {
        $('body').addClass('without-banner');
      }
    }
  };

  // Show/hide desktop search block.
  Drupal.behaviors.openySearchToggle = {
    attach: function (context, settings) {
      var search_md = $('.site-search button');
      var main_menu_links_md = $('.page-head__main-menu .nav-level-1 li:not(:eq(0))').find('a, button');
      var search_close_md = $('.page-head__search-close');

      search_md.once('openy-search-toggle-hide').on('click', function () {
        main_menu_links_md.removeClass('show').addClass('fade');
      });

      search_close_md.once('openy-search-toggle-show').on('click', function () {
        main_menu_links_md.addClass('show');
      });
    }
  };

  // Add class to header when mobile menu is opened.
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

  // Re-size.
  $(window).resize(function () {
    Drupal.behaviors.openyDropdownMenu.attach();
  });

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

  // Program Carousels
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

  // Match Heights
  Drupal.behaviors.openyMatchHeight = {
    attach: function (context, settings) {
      matchAllHeight();
    }
  };

  function matchAllHeight() {
    var el = [
      '.viewport .page-head__main-menu .nav-level-3 > a',
      '.blog-heading',
      '.membership-type',
      '.membership-type h3',
      '.membership-type article p',
      '.activity-group .card'
    ];

    // make them all equal heights.
    $.each(el, function (index, value) {
      $(value).matchHeight();
    });
  }

})(jQuery);
