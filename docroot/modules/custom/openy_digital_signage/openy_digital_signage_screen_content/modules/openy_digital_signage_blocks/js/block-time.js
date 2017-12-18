/**
 * @file
 * Block behaviors.
 */
(function ($, window, Drupal) {

  'use strict';

  /**
   * Provide the initialization of the time library.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block.
   */
  Drupal.behaviors.openyDigitalSignageBlockTime = {
    attach: function (context, settings) {

      var time_block = $('.openy-digital-signage-block-time .time');

      /**
       * Update time.
       */
      function updateTime() {
        var time = window.tm.getTime() * 1000;
        // TODO: pull in the site timezone configuration.
        var html = moment(time).tz('America/Chicago').format('h:mm a');
        time_block.html(html);
        setTimeout(updateTime, 1000);
      }

      $(context).find(time_block).once('block-time').each(updateTime);
    }
  };

})(jQuery, window, Drupal);