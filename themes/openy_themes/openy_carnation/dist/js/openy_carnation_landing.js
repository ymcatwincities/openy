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
