/**
 * @file mobile_gtranslate.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviors related to blocks.
   */
  Drupal.behaviors.MobileGTranslate = {
    attach: function (context) {
      $(".navbar-toggler").on('click', function () {
        setTimeout(function () {
          var el = $('#block-openy-lily-googletranslate');
          console.log('test');
          el.addClass('mobile');
          $(el).appendTo('#sidebar-nav > li > ul > li.more');
        }, 100);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
