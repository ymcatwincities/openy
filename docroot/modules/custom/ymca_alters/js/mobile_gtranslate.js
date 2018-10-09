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
      var el = $('#google_translate_element');
      $(".navbar-toggler").on('click', function () {
        el.appendTo('#sidebar-nav > li > ul > li.more');
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
