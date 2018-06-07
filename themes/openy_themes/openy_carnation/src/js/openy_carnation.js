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
      var banner_header = $('.paragraph--type--banner, .landing-header');
      if (banner_header.length > 0) {
        $('.banner-zone-node').once('move').append(banner_header.eq(0));
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

      search_md.once('search-toggle-hide').on('click', function () {
        main_menu_links_md.removeClass('show').addClass('fade');
      });

      search_close_md.once('search-toggle-show').on('click', function () {
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

  // Match Heights
  Drupal.behaviors.openyMatchHeight = {
    attach: function (context, settings) {
      matchAllHeight();
    }
  };

  function matchAllHeight() {
    var el = [
      // '.container-wide .row > div'
    ];

    // make them all equal heights.
    $.each(el, function (index, value) {
      $(value).matchHeight();
    });
  }

})(jQuery);
