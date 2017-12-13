/**
 * @file
 * Provides OpenY Digital Signage screens related behavior.
 */

/**
 * @namespace
 */
Drupal.openyDigitalSignageBlocks = Drupal.openyDigitalSignageBlocks || {};

/**
 * TimeManager class.
 *
 * Makes it possible to override current page time.
 */
function TimeManager() {
  var self = this;

  this.init = function () {
    self.initTime = (new Date()).getTime() / 1000;
    self.offset = 0;
    self.speed = 1;
    self.query_params = self.get_query_param();
    if (self.query_params.hasOwnProperty('time')) {
      self.offset = parseInt(self.query_params.time);
    }
    else if (self.query_params.hasOwnProperty('from')) {
      self.offset = parseInt(self.query_params.from);
    }
    if (self.offset) {
      self.speed = 20;
    }

    if (self.query_params.hasOwnProperty('speed')) {
      self.speed = parseFloat(self.query_params.speed);
    }
  };

  this.getTime = function() {
    var realTime = self.getRealTime();
    if (self.offset) {
      return self.offset + (realTime - self.initTime) * self.speed;
    }

    return realTime;
  };

  this.getRealTime = function () {
    return (new Date()).getTime() / 1000;
  };

  // Extracts query params from url.
  this.get_query_param = function () {
    var query_string = {};
    var query = window.location.search.substring(1);
    var pairs = query.split('&');
    for (var i = 0; i < pairs.length; i++) {
      var pair = pairs[i].split('=');

      // If first entry with this name.
      if (typeof query_string[pair[0]] === 'undefined') {
        query_string[pair[0]] = decodeURIComponent(pair[1]);
      }
      // If second entry with this name.
      else if (typeof query_string[pair[0]] === 'string') {
        query_string[pair[0]] = [
          query_string[pair[0]],
          decodeURIComponent(pair[1])
        ];
      }
      // If third or later entry with this name
      else {
        query_string[pair[0]].push(decodeURIComponent(pair[1]));
      }
    }

    return query_string;
  };

  this.init();

  return this;
}

(function ($, window, Drupal, drupalSettings) {
  'use strict';

  function OpenYDigitalSignageObjectsManager() {
    var self = this;
    this.getObject = function(el) {
      if ($(el).is('.screen')) {
        return self.getScreen(el);
      }
      else if ($(el).is('.screen-content')) {
        return self.getScreenContent(el);
      }
      else if ($(el).is('.block')) {
        return self.getScreenContentBlock(el);
      }

      return null;
    };

    this.getScreen = function (el) {
      var screen = $(el).data('screen');
      if (typeof screen == 'undefined') {
        screen = new OpenYScreen(el);
      }
      return screen;
    };

    this.getScreenContent = function (el) {
      var screenContent = $(el).data('screenContent');
      if (typeof screenContent == 'undefined') {
        screenContent = new OpenYScreenContent(el);
      }
      return screenContent;
    };

    this.getScreenContentBlock = function (el) {
      var screenContentBlock = $(el).data('screenContentBlock');
      if (typeof screenContentBlock == 'undefined') {
        screenContentBlock = new OpenYScreenContentBlock(el);
      }
      return screenContentBlock;
    };

    return this;
  }

  window.ObjectsManager = new OpenYDigitalSignageObjectsManager();

  function DependencyManager() {
    this.dependents = [];

    this.addDependent = function(dependent) {
      this.dependents[dependent.id()] = dependent;
    };

    this.removeDependent = function(dependent) {
      dependent.remove();
      delete this.dependents[dependent.id()];
    };

    return this;
  }

  function OpenYScreen(el) {
    var self = this;
    this.options = {
      animation: 5000,
      screenUpdatePeriod: 10000,
      scheduleUpdatePeriod: 59
    };
    this.lastUpdate = window.tm.getTime();
    // Store element.
    this.element = $(el);
    // Store object.
    this.element.data('screen', self);

    this.init = function() {
      self.loopCallback();
      self.loop = setInterval(self.loopCallback, self.options.screenUpdatePeriod);
      self.element
        .css({opacity: 0, display: 'block'})
        .animate({opacity: 1}, self.options.animation, function() {
          self.afterInit();
        });
      $("body > .loader").fadeOut(self.options.animation);
    };

    this.afterInit = function() {
      self.element.addClass('screen-active');
    };

    this.loopCallback = function() {
      if (window.tm.getTime() - self.lastUpdate > self.options.scheduleUpdatePeriod) {
        // Update schedule.
        self.updateSchedule();
      }
      else {
        // Update Screen contents.
        self.updateScreenContents();
      }
    };

    this.updateScreenContents = function() {
      // Update current screen content.
      var screenContents = self.getScreenContents();
      var time = window.tm.getTime();
      $(screenContents).each(function(o) {
        var screenContent = ObjectsManager.getObject(this);
        var workingHours = screenContent.getWorkingHours();
        if (time >= workingHours.from && time < workingHours.to) {
          screenContent.activate();
        }
        else {
          screenContent.deactivate();
        }
      });
    };

    this.updateSchedule = function() {
      var time = window.tm.getTime();
      // Download the fresh data.
      $.get(window.location.href + '?' + time, function(data) {
        // Store last update timestamp.
        self.lastUpdate = window.tm.getTime();
        var $data = $(data);
        self
          .getScreenContents()
          .each(function () {
            var screenContent = ObjectsManager.getObject(this);
            var workingHours = screenContent.getWorkingHours();
            if (time > workingHours.to) {
              screenContent.deactivate();
              screenContent.element.remove();
            }
          });

        if ($data.find('.screen').size() > 0) {
          var incoming_screen_elem = $data.find('.screen').get(0);
          var incoming_screen = ObjectsManager.getObject(incoming_screen_elem);
          // Force reloading if the app version has changed.
          if (incoming_screen.getAppVersion() !== self.getAppVersion()) {
            window.location.reload();
          }

          // Check if the existing screen contents were removed in the update.
          var screenContents = self.getScreenContents();
          screenContents
            .each(function () {
              var existingScreenContent = ObjectsManager.getObject(this);
              var active = existingScreenContent.isActive();
              var id = existingScreenContent.getId();
              var new_screen = $data
                .find('.screen-content[data-screen-content-id="' + id + '"]')
                .each(function () {
                  if ($(this).data('hash') != existingScreenContent.element.data('hash')) {
                    // Append new item.
                    $(this).insertAfter(existingScreenContent.element);
                    $(this).attr('new', 'new');
                    // Remove outdated item.
                    if (!active) {
                      existingScreenContent.element.remove();
                    }
                    else {
                      // Update without animations.
                      var newScreenContent = ObjectsManager.getObject(this);
                      existingScreenContent.deactivateNoDelay();
                      newScreenContent.activateNoDelay();
                    }
                  }
                  else {
                    // Incoming screen content doesn't contain any changes.
                    $(this).remove();
                  }
                });

              // No corresponding screen content was found.
              if (new_screen.size() === 0) {
                // Set "to"-time in past so that it removed later.
                existingScreenContent.element.data('to-ts', time - 1);
                if (!active) {
                  existingScreenContent.element.remove();
                }
              }

            });

          // Append all the rest incoming items.
          $data.find('.screen-content').each(function () {
            $(this).appendTo(self.element);
          });
        }
        Drupal.attachBehaviors(self.element);
        self.updateScreenContents();
      });
    };

    this.getScreenContents = function() {
      return self.element.find('.screen-content');
    };

    this.getAppVersion = function() {
      return self.element.data('app-version');
    };

    return this;
  }

  function OpenYScreenContent(el) {
    var self = this;
    this.options = { animation: 1000 };
    // Store element.
    this.element = $(el);
    // Store object.
    this.element.data('screenContent', this);

    this.getBlocks = function() {
      return self.element.find('.block');
    };

    this.getId = function () {
      return self.element.data('screen-content-id');
    };

    this.activate = function() {
      if (self.isActive()) {
        return;
      }
      self.element
        .css({position: 'absolute', top: '100vh'})
        .addClass('screen-content-activating')
        .animate({top: 0}, self.options.animation, function () {
          $(this)
            .css({position: 'static'})
            .removeClass('screen-content-inactive')
            .removeClass('screen-content-activating')
            .addClass('screen-content-active');
        });
      self.getBlocks().each(function () {
        var block = ObjectsManager.getObject(this);
        block.activate();
      });
    };

    this.activateNoDelay = function() {
      if (self.isActive()) {
        return;
      }
      self.getBlocks().each(function () {
        var block = ObjectsManager.getObject(this);
        block.activate();
      });
      self.element
        .removeClass('screen-content-inactive')
        .removeClass('screen-content-activating')
        .addClass('screen-content-active');
    };

    this.deactivateNoDelay = function() {
      if (!self.isActive()) {
        return;
      }
      self.getBlocks().each(function() {
        var block = ObjectsManager.getObject(this);
        block.deactivate();
      });
      self.element.remove();
    };

    this.deactivate = function() {
      if (!self.isActive()) {
        return;
      }

      self.element
        .css({position: 'absolute', top: 0})
        .animate({ top: '-100vh' }, self.options.animation, function() {
          $(this)
            .removeClass('screen-content-active')
            .addClass('screen-content-inactive')
            .remove();
        });
      self.getBlocks().each(function() {
        var block = ObjectsManager.getObject(this);
        block.deactivate();
      });
    };

    this.isActive = function() {
      return self.element.hasClass('screen-content-active');
    };

    this.getWorkingHours = function() {
      return {
        from: self.element.data('from-ts'),
        to: self.element.data('to-ts')
      };
    };

    return this;
  }

  function OpenYScreenContentBlock(el) {
    var self = this;
    // Store element.
    this.element = $(el);
    // Store object.
    this.element.data('screenContentBlock', this);

    this.getBlocks = function() {
      return self.element.find('.block');
    };

    this.activate = function() {
      self.element
        .addClass('active-block');
    };

    this.deactivate = function() {
      self.element
        .removeClass('active-block');
    };

    this.isActive = function() {
      return self.element
        .hasClass('active-block');
    };

    return this;
  }

  /**
   * Creates an instance or TimeManager.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the global OpenYScreen object to the available screen objects.
   */
  Drupal.behaviors.screen_time_manager = {
    attach: function (context, settings) {
      if (context == window.document) {
        window.tm = new TimeManager();
      }
    }
  };

  /**
   * Attaches the global OpenYScreen object to the available screen objects.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the global OpenYScreen object to the available screen objects.
   */
  Drupal.behaviors.screen_handler = {
    attach: function (context, settings) {
      $('.screen', context).once().each(function () {
        var screen = new OpenYScreen(this);
        screen.init();
      });
    }
  };

})(jQuery, window, Drupal, drupalSettings);
