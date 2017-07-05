/**
 * @file
 * Provides OpenY Digital Signage layouts related behavior.
 */
;(function ($, window, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the behavior to schedule items.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds proper orientation classes to all the output layouts.
   */
  Drupal.behaviors.screen_schedule_timeline = {
    attach: function (context, settings) {

      $('.screen-schedule-timeline .screen-schedule-item', context)
        .once()
        .each(function() {
          var $self = $(this);

          var from = $self.data('from');
          var to = $self.data('to');

          $(this).css({
            top: (100 * from / 24) + '%',
            height: (100 * (to - from) / 24) + '%'
          });
        })
        .click(function(e) {
          $(this).find('.screen-schedule-item__title').trigger('click');
        })
        .find('a')
          .on('click', function() {
            $('.screen-schedule-timeline .screen-schedule-item', context).removeClass('screen-schedule-item--active');
            $(this).parent('.screen-schedule-item').addClass('screen-schedule-item--active');
          });
    }
  };

  /**
   * Attaches the behavior to current time indicator.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds proper orientation classes to all the output layouts.
   */
  Drupal.behaviors.screen_schedule_timeline_current_time = {
    attach: function (context, settings) {
      $('.screen-schedule-timeline__current-time', context)
        .once()
        .each(function() {
          var $self = $(this);
          var current = $self.data('current-time');
          $(this).css({
            top: (100 * current / 24) + '%'
          });
        });
    }
  };

  /**
   * Attaches the to frames.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds proper orientation classes to all the output layouts.
   */
  Drupal.behaviors.screen_schedule_frame = {
    attach: function (context, settings) {
      $('.frame-container', context).once().each(function() {
        var $self = $(this);
        var src = $self.data('src');
        $(this).append($('<iframe src="' + src + '">'));
      });
    }
  };


})(jQuery, window, Drupal, drupalSettings);
