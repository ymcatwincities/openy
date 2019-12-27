/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

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

  // Re-size.
  $(window).resize(function () {
    Drupal.behaviors.openyDropdownMenu.attach();
  });

  /**
   * BS4 data-spy: affix replacement
   */
  Drupal.behaviors.openyHeaderAffix = {
    attach: function (context, settings) {
      $(window).once('openy-affix', context).on('scroll', function (event) {
        var scrollValue = $(window).scrollTop();
        if (scrollValue === settings.scrollTopPx || scrollValue > 1) {
          $('.top-navs').addClass('affix');
        }
        else if (scrollValue === settings.scrollTopPx || scrollValue < 1) {
          $('.top-navs').removeClass('affix');
        }
      });
    }
  };

  /**
   * Make work with main nav accessible. User could walk through submenu infinitely.
   * @type {{attach: Drupal.behaviors.menuTabNav.attach}}
   */
  Drupal.behaviors.menuTabNav = {
    attach: function(context, settings) {
      $('.navbar .row-level-2').each(function(index, value) {
        var aLast = $(value).find('a').last();
        var aFirst = $(value).find('a').first();
        aLast.focusout(function (event) {
          event.stopPropagation();
          aFirst.focus();
        });
      });
  // Add ability to walk through search input and close search buttons at search action.
      $('.navbar .search-input').focusout(function (event) {
        event.stopPropagation();
        $('.page-head__search-close').focus();
      });
    }
  };


})(jQuery);
