/**
 * @file
 * Block behaviors.
 */
(function ($, window, Drupal) {

  'use strict';

  /**
   * Static bar specific behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block.
   */
  Drupal.behaviors.openyDigitalSignageBlockClassCurrent = {
    attach: function (context, settings) {
      $('.block-class-current', context).once().each(function () {
        var blockProto = new OpenYDigitalSignageBlockClassCurrent(this);
      });
    }
  };

  function OpenYDigitalSignageBlockClassCurrent(context) {
    this.context = context;
    var self = this;

    // General loop – basically swaps classes.
    this.loop = function () {
      var classes = self.getCurrentAndNext();
      if (self.needsActiveClassActualization(classes)) {
        self.actualizeActiveClasses(classes);
        self.updateProgressBars();
      }
    };

    // Fast loop - basically updates the awaiting class.
    this.fastloop = function () {
      var $awaiting = self.getAwaitingClass();
      $awaiting.each(function () {
        var starts_in = $(this).data('from') - self.getTimeOffset();
        if (starts_in < 0) {
          self.updateProgressBars();
          $(this).removeClass('class-awaiting');
          $('.has-class-awaiting', self.context).removeClass('has-class-awaiting');
          return;
        }

        var formattedTime = self.formatTime(starts_in);
        $(this).find('.class-time-countdown-time--value').html(formattedTime.string);
        $(this).find('.class-time-countdown-time--suffix').text(formattedTime.suffix);

        if (starts_in < 3600) {
          self.drawAwaitProgress(starts_in / 3600);
        }
      });
    };

    // Formats time.
    this.formatTime = function (seconds) {
      var fHours, fMinutes, fSeconds, separator;
      separator = Math.floor(seconds) % 2 ? '<span class="separator">:</span>' : '<span class="separator odd">:</span>';
      if (seconds < 3600) {
        fMinutes = Math.floor(seconds / 60);
        fSeconds = Math.floor(seconds - fMinutes * 60);
        if (fSeconds < 10) fSeconds = '0' + fSeconds;
        if (fMinutes < 10) fMinutes = '0' + fMinutes;

        return {
          suffix: seconds > 59 ? 'minutes' : 'seconds',
          string: fMinutes + separator + fSeconds
        };
      }

      fHours = Math.floor(seconds / 3600);
      fMinutes = Math.floor((seconds - fHours * 3600) / 60);
      if (fMinutes < 10) fMinutes = '0' + fMinutes;

      return {
        suffix: 'hours',
        string: fHours + separator + fMinutes
      };
    };

    // Updates progress on the awaiting class.
    this.drawAwaitProgress = function (percentage) {
      percentage = percentage * 100;
      var activeColor = '#f36f3c',
        passiveColor = '#ffefcf';
      var activeBorder = $('.class-time-countdown-progress');
      if (percentage > 100) percentage = 100;
      var deg = percentage * 3.6;
      if (deg <= 180){
        activeBorder.css('background-image','linear-gradient(' + (90+deg) + 'deg, transparent 50%, ' + passiveColor + ' 50%), linear-gradient(90deg, ' + passiveColor + ' 50%, transparent 50%)');
      }
      else{
        activeBorder.css('background-image','linear-gradient(' + (deg-90) + 'deg, transparent 50%, ' + activeColor + ' 50%), linear-gradient(90deg, ' + passiveColor + ' 50%, transparent 50%)');
      }
    };

    // Updates progress bar of the current class.
    this.updateProgressBars = function () {
      var $class = $('.active-classes .class-active .class', self.context);
      var offset = self.getTimeOffset();
      var from = $class.data('from');
      var to = $class.data('to');
      var progress = 100 * (offset - from) / (to - from);
      $class.find('.class-time-frame-progress-bar').css({width: progress + '%'});
      $class.find('.class-time-frame-progress-bar').stop().animate({width:'100%'}, (to - offset) * 1000, 'linear');
    };

    // Checks if the current class needs replacing.
    this.needsActiveClassActualization = function (classes) {
      var $activeClassContainer = $('.active-classes .class-active', self.context);
      var $activeClass = $('.class', $activeClassContainer);
      if (!$activeClass.size() || $activeClass.data('from') != classes.last.data('from')) {
        return true;
      }

      var $upcomingClassContainer = $('.active-classes .class-next', self.context);
      var $upcomingClass = $('.class', $upcomingClassContainer);
      if (!$upcomingClass.size() || $upcomingClass.data('from') != classes.next.data('from')) {
        return true;
      }

      return false;
    };

    // Changes the current class with the upcoming.
    this.actualizeActiveClasses = function (classes) {
      var $activeClasses = $('.active-classes', self.context);
      var $prevClassContainer = $('.active-classes .class-prev', self.context);
      var $activeClassContainer = $('.active-classes .class-active', self.context);
      var $activeClass = $('.class', $activeClassContainer);
      var $upcomingClassContainer = $('.active-classes .class-next', self.context);
      var $upcomingClass = $('.class', $upcomingClassContainer);

      if ($activeClass.size()) {
        // Remove previous class.
        $prevClassContainer.remove();

        // Slide active class up.
        $activeClassContainer
          .removeClass('class-active')
          .addClass('class-prev');

        setTimeout(function() {
          // Slide Upcomming Class Up
          $upcomingClassContainer
            .removeClass('class-next')
            .addClass('class-active');

          $activeClasses.removeClass('has-class-awaiting');
          $upcomingClass = $('.class', $upcomingClassContainer);
          if ($upcomingClass.data('from') > self.getTimeOffset()) {
            $upcomingClass.addClass('class-awaiting');
            $activeClasses.addClass('has-class-awaiting');
          }

          // Create new Upcoming class.
          $upcomingClassContainer = $("<div class='class-next' />").appendTo($activeClasses);
          $upcomingClassContainer.append(classes.next.clone(true));

          self.updateProgressBars();
        }, 3000);
      }
      else {
        $activeClassContainer.empty().append(classes.last.clone(true));
        $upcomingClassContainer.empty().append(classes.next.clone(true));

        $activeClass = $('.class', $activeClassContainer);
        if ($activeClass.data('from') > self.getTimeOffset()) {
          $activeClass.addClass('class-awaiting');
          $activeClasses.addClass('has-class-awaiting');
        }
        self.updateProgressBars();
      }
    };

    // Returns current time.
    this.getTimeOffset = function () {
      return window.tm.getTime();
    };

    // Returns current and the upcoming classes.
    this.getCurrentAndNext = function () {
      var $current = null, $next = null;
      var offset = self.getTimeOffset();
      $('.all-classes .class', self.context).each(function(){
        var $this = $(this);
        if ($this.data('to') >= offset) {
          if (!$current) {
            $current = $this;
          }
          else if ($this.data('from') < $current.data('from')) {
            $current = $this;
          }
          if ($current && $this.data('from') >= $current.data('to')) {
            if (!$next || $this.data('from') < $next.data('from')) {
              $next = $this;
            }
          }
        }
      });

      return {
        last: $current,
        next: $next
      }
    };

    // Returns the awaiting class.
    this.getAwaitingClass = function () {
      return $('.active-classes .class-awaiting', self.context);
    };

    // Activates the block.
    this.activate = function () {
      self.actualizeActiveClasses(self.getCurrentAndNext());
      self.fastloop();
      self.timer = setInterval(self.loop, 5000);
      self.fasttimer = setInterval(self.fastloop, 1000);
      self.activated = self.getTimeOffset();
    };

    // Deactivates the block.
    this.deactivate = function () {
      clearInterval(self.timer);
      clearInterval(self.fasttimer);
    };

    self.blockObject = ObjectsManager.getObject(self.context);
    self.blockObject.activate = self.activate;
    self.blockObject.deactivate = self.deactivate;
    if (self.blockObject.isActive() || $(self.context).parents('.screen').size() == 0) {
      self.activate();
    }

    return this;
  }

})(jQuery, window, Drupal);
