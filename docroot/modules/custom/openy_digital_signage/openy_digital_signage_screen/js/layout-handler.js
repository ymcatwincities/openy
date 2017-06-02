;(function ($) {
  'use strict';

  Drupal.behaviors.layout_handler = {
    attach: function (context, settings) {

      jQuery(window).once().resize(function() {
        var o = jQuery(window).width() > jQuery(window).height() ? 'landscape' : 'portrait';
        jQuery('.openy-ds-layout', context)
          .removeClass('landscape')
          .removeClass('portrait')
          .addClass(o);
      }).trigger('resize');

    }
  };
})(jQuery);
