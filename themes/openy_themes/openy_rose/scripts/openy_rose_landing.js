(function ($) {
  'use strict';
  Drupal.behaviors.openy_rose_fixed_sidbar = {
    attach: function (context, settings) {
      function sidebarAffix() {
        var contentHeight = $('.main-region').outerHeight();
        var sidebarHeight = $('.sidebar-region').outerHeight();

        if (contentHeight > sidebarHeight) {
          var $sidebar = $('.landing-sidebar.two-column-fixed>.wrapper-field-sidebar-content');
          $sidebar.unbind();
          var top_offset = $('.header-alerts-list').outerHeight(true) + $('.wrapper-field-header-content').outerHeight(true) + 48;
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
      $(window).on('resize.sidebarAffix', sidebarAffix).trigger('resize.sidebarAffix');
    }
  };
})(jQuery);
