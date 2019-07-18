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
