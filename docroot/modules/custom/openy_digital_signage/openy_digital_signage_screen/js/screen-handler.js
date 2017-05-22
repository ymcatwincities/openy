;function TimeManager() {

  this.getTime = function() {
    return (new Date).getTime() / 1000;
  };

  return this;
};

(function ($) {
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

  var ObjectsManager = new OpenYDigitalSignageObjectsManager();

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
      screenUpdatePeriod: 15000,
      scheduleUpdatePeriod: 30
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
        // Update Screen contents
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
      $.get(window.location.href + '?' + time, function(data) {
        self.lastUpdate = window.tm.getTime();
        var $data = $(data);
        self
          .getScreenContents()
          .each(function () {
            var screenContent = ObjectsManager.getObject(this);
            var workingHours = screenContent.getWorkingHours();
            if (time < workingHours.from) {
              screenContent.deactivate();
              screenContent.element.remove();
            }
          });
        $data
          .find('.screen > .screen-content')
          .each(function () {
            var screenContent = ObjectsManager.getObject(this);
            var workingHours = screenContent.getWorkingHours();
            if (time < workingHours.to) {
              var id = screenContent.getId();
              var wasActive = false;
              self.element
                .find('.screen-content[data-screen-content-id='+id+']')
                .each(function() {
                  var existingScreenContent = ObjectsManager.getObject(this);
                  if (existingScreenContent.isActive()) {
                    wasActive = true;
                  }
                  existingScreenContent.deactivate();
                  $(this).remove();
                });
              $(this).appendTo(self.element);
              if (wasActive) {
                screenContent.activateNoDelay();
              }
            }
          });
        Drupal.attachBehaviors(self.element);
        self.updateScreenContents();
      });
    };

    this.getScreenContents = function() {
      return self.element.find('.screen-content');
    };

    this.init();

    return this;
  }

  function OpenYScreenContent(el) {
    var self = this;
    this.options = { animation: 1000 };
    // Store element.
    this.element = $(el);
    // Store object.
    this.element.data('screenContent', this);
    console.log('init screen content');

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
      self.element
        .removeClass('screen-content-inactive')
        .removeClass('screen-content-activating')
        .addClass('screen-content-active');
      self.getBlocks().each(function () {
        var block = ObjectsManager.getObject(this);
        block.activate();
      });
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
    console.log('init screen content block');

    this.getBlocks = function() {
      return self.element.find('.block');
    };

    this.activate = function() {
      self.element
        .addClass('active-block').
      css({border: '1px solid red'});
    };

    this.deactivate = function() {
      self.element
        .removeClass('active-block')
        .css({border: '5px solid black'});
    };

    return this;
  }

  Drupal.behaviors.screen_handler = {
    attach: function (context, settings) {

      $(".screen").once().each(function () {
        window.tm = new TimeManager();
        console.log(window.tm.getTime());

        var screen = new OpenYScreen(this);
      });
    }
  };
})(jQuery);
