/*
(function ($) {
  'use strict';
  Drupal.behaviors.openy_carnation_fixed_sidbar = {
    attach: function (context, settings) {

      function sidebarAffix() {
        var windowWidth = window.innerWidth;
        var leftOffset = 0;
        var toptOffset = 0;
        var contenWidth = $('.main-region').outerWidth();
        var sidebar = $('.sidebar-region');
        var sidebarWidth = sidebar.outerWidth();
        
        if (windowWidth > 1024) {
          var bigPaddings = (windowWidth - contenWidth- sidebarWidth)/2;
          var additionalOffset = 40;
          if (bigPaddings > 23 && bigPaddings < 50) {
            additionalOffset = 0;
          }
          toptOffset = '220px';
          leftOffset = windowWidth - (windowWidth - contenWidth- sidebarWidth)/2 - contenWidth + sidebarWidth + additionalOffset + 'px';
        }
        if (windowWidth >= 992 && windowWidth <= 1024) {
          toptOffset = '180px';
          leftOffset = windowWidth - (windowWidth - contenWidth)  + 5 + 'px';
        }

        var scrollValue = $(window).scrollTop();

        var $headerHeight = $('.wrapper-field-header-content').height();
        if (windowWidth >= 992 && scrollValue >= $headerHeight + 200) {
          sidebar
              //.addClass('fixed-top')
              .css('position', 'fixed')
              .css('top', toptOffset)
              .css('left', leftOffset)
              .css('width', 348 + 'px')
          ;
        }
        else {
          sidebar
              //.removeClass('fixed-top')
              .css('position', 'relative')
              .css('top', '0')
              .css('left', '0');
        }
      }
      $(window).on('scroll', sidebarAffix);
      $(window).on('resize', sidebarAffix);
    }
  };
})(jQuery);
*/

(function ($) {
  'use strict';
  Drupal.behaviors.openy_carnation_fixed_sidbar = {
    attach: function (context, settings) {
      function sidebarAffix(ev) {
        var contentHeight = $('.main-region').outerHeight();
        var sidebarHeight = $('.sidebar-region').outerHeight();

        var headerWrapperHeight = $('.wrapper-field-header-content').outerHeight();

        if (contentHeight >= sidebarHeight) {
          var $sidebar = $('.landing-content.two-column-fixed .wrapper-field-sidebar-content');
          $sidebar.unbind();
          var $headerHeight = $('.wrapper-field-header-content').height();
          var top = 0;
          if ($headerHeight < 550) {
            top = 70;
          }
          else {
            top = 120;
          }
          var top_offset = $('.header-alerts-list').outerHeight(true) + $('.wrapper-field-header-content').outerHeight(true) + top;
          var bottom_offset = $('.footer').outerHeight(true) + $('.wrapper-field-bottom-content').outerHeight(true) + $('.site-alert--footer').outerHeight(true);
          $sidebar.affix({
            offset: {
              top: top_offset,
              bottom: bottom_offset
            }
          });
          $sidebar.on('affixed.bs.affix', function () {
            $sidebar.attr('style', '');
          });
        }
      }
      setTimeout(function() {
        $(window).on('resize.sidebarAffix', sidebarAffix).trigger('resize.sidebarAffix');
      }, 100);
    }
  };
})(jQuery);
