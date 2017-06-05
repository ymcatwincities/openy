/**
 * @file
 * Provides OpenY Digital Signage layouts related behavior.
 */
;(function ($, window, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the behavior to window object once.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds proper orientation classes to all the output layouts.
   */
  Drupal.behaviors.layout_handler = {
    attach: function (context, settings) {

      $(window).once().resize(function() {
        var o = $(window).width() > $(window).height() ? 'landscape' : 'portrait';
        $('.openy-ds-layout', context)
          .removeClass('landscape')
          .removeClass('portrait')
          .addClass(o);
      }).trigger('resize');
    }
  };
})(jQuery, window, Drupal, drupalSettings);
