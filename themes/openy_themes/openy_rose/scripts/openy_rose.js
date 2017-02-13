(function ($) {
  "use strict";
  Drupal.openy_rose =  Drupal.openy_rose || {};
  Drupal.behaviors.openy_rose_theme = {
    attach: function (context, settings) {
      $('.ui-tabs').tabs({
        active: false,
        collapsible: true
      });
    }
  };
})(jQuery);
