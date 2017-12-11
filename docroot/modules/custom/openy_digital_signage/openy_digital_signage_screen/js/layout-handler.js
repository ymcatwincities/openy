/**
 * @file
 * Provides OpenY Digital Signage layouts related behavior.
 */
(function ($, window, Drupal, drupalSettings) {

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
      if (context == window.document) {
        $(window).once().resize(function () {
          Drupal.behaviors.layout_handler.recalc($('.openy-ds-layout', context));
        }).trigger('resize');
      }
      else if ($(context).is('.openy-ds-layout')) {
        Drupal.behaviors.layout_handler.recalc($(context));
      }
      else {
        Drupal.behaviors.layout_handler.recalc($('.openy-ds-layout', context));
      }
    },

    recalc: function ($layouts) {
      var o = $(window).width() > $(window).height() ? 'landscape' : 'portrait';
      $layouts
        .removeClass('landscape')
        .removeClass('portrait')
        .addClass(o);
    }
  };
})(jQuery, window, Drupal, drupalSettings);
