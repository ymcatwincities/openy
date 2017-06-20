(function ($) {
  "use strict";
  Drupal.behaviors.openy_rose_fixed_sidbar = {
    attach: function (context, settings) {
      function sidebarAffix() {
        var contentHeight = $(".main-region").outerHeight();
        var sidebarHeight = $(".sidebar-region").outerHeight();

        if (contentHeight > sidebarHeight) {
          var $sidebar = $(".landing-sidebar.two-column-fixed>div");
          $sidebar.unbind();
          var top_offset = $('.nav-global').outerHeight(true) + $('.site-alert--header').outerHeight(true) + $('.landing-header').outerHeight(true) + $('#block-tabs').outerHeight(true);
          var bottom_offset = $(".footer").outerHeight(true) + 40;
          $sidebar.affix({
            offset: {
              top: top_offset,
              bottom: bottom_offset
            }
          });
          $sidebar.on("affixed.bs.affix", function () {
            $sidebar.attr("style", "");
          });
        }
      }
      $(window).on('resize.sidebarAffix', sidebarAffix).trigger('resize.sidebarAffix');
    }
  };
})(jQuery);
