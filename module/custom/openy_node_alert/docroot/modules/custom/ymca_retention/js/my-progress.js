(function ($) {

  Drupal.behaviors.ymca_retention_progress = {};
  Drupal.behaviors.ymca_retention_progress.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-my-progress-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-my-progress-processed');

    Drupal.ymca_retention.angular_app.controller('MyProgressController', function (storage, $state) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.visitsGoal = function () {
        if (typeof self.storage.member === 'undefined' || !self.storage.member || typeof self.storage.member.visitsGoal === 'undefined') {
          return 9;
        }

        return self.storage.member.visitsGoal;
      };

      self.pointClass = function (i) {
        return 'point point--' + self.visitsGoal() + '-' + i;
      };
      
      self.points = function () {
        var p = [];
        for (var i = 1; i < self.visitsGoal(); i++) {
          p.push(i);
        }

        return p;
      };

      self.checkInClass = function (date) {
        if (typeof self.storage.member_checkins === 'undefined' || !self.storage.member_checkins) {
          return '';
        }
        var classes = [];

        if (date.future) {
          classes.push('compain-progress-block--upcoming');
        }
        else {
          classes.push('compain-progress-block--checked');
        }

        return classes.join(' ');
      };

      self.checkInBonusClass = function (date) {
        if (self.isBonus(date) || date.today || date.future) {
          return self.checkInClass(date);
        }

        return  self.checkInClass(date) + ' compain-progress-block--no-bonus';
      };

      self.checkInBonusImageClass = function (date) {
        if (typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings || date.future) {
          return self.checkInBonusClass(date);
        }
        if (typeof self.storage.spring2017campaign.bonuses_settings[date.timestamp] !== 'undefined') {
          return self.checkInBonusClass(date) + ' compain-progress-block--img';
        }

        return self.checkInBonusClass(date);
      };

      self.checkInStatus = function (date) {
        if (typeof self.storage.member_checkins === 'undefined' || !self.storage.member_checkins || date.future) {
          return Drupal.t('Upcoming');
        }
        if (self.storage.member_checkins[date.timestamp] == 1) {
          return Drupal.t('Visited');
        }

        return Drupal.t('Missed');
      };

      self.isBonus = function (date) {
        if (typeof self.storage.member_bonuses === 'undefined' || !self.storage.member_bonuses || date.future) {
          return false;
        }

        return typeof self.storage.member_bonuses[date.timestamp] !== 'undefined';
      };

      self.bonusTitle = function (date) {
        if (typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings || date.future) {
          return '';
        }
        if (typeof self.storage.spring2017campaign.bonuses_settings[date.timestamp] !== 'undefined') {
          return self.storage.spring2017campaign.bonuses_settings[date.timestamp].title;
        }

        return '';
      };

      self.tipNumber = function (date) {
        if (typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings || date.future) {
          return '';
        }
        if (typeof self.storage.spring2017campaign.bonuses_settings[date.timestamp] !== 'undefined') {
          return self.storage.spring2017campaign.bonuses_settings[date.timestamp].tip;
        }

        return '';
      };

      self.bonusesCount = function () {
        if (typeof self.storage.campaign.dates === 'undefined' || !self.storage.campaign.dates ||
          typeof self.storage.member_bonuses === 'undefined' || !self.storage.member_bonuses) {
          return 0;
        }

        var bonuses = 0;
        for (var i = 0; i < self.storage.campaign.dates.length; i++) {
          var date = self.storage.campaign.dates[i];
          if (date.future) {
            break;
          }
          if (typeof self.storage.member_bonuses[date.timestamp] !== 'undefined') {
            bonuses++;
          }
        }

        return bonuses;
      };

      self.visitsLeft = function () {
        return self.visitsGoal() - self.visitsCount();
      };

      self.visitsCount = function () {
        if (typeof self.storage.campaign.dates === 'undefined' || !self.storage.campaign.dates ||
          typeof self.storage.member_checkins === 'undefined' || !self.storage.member_checkins) {
          return 0;
        }

        var visits = 0;
        for (var i = 0; i < self.storage.campaign.dates.length; i++) {
          var date = self.storage.campaign.dates[i];
          if (date.future) {
            break;
          }
          if (self.storage.member_checkins[date.timestamp] == 1) {
            visits++;
          }
        }

        if (visits > self.visitsGoal()) {
          visits = self.visitsGoal();
        }

        return visits;
      };

      self.isGoalAchieved = function () {
        return self.visitsGoal() == self.visitsCount();
      };

      self.visitsCountClass = function () {
        return 'compain-progress--fill-' + self.visitsGoal() + '--' + self.visitsCount();
      };

      self.bonusImage = function (date) {
        if (typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings || date.future) {
          return {};
        }
        if (typeof self.storage.spring2017campaign.bonuses_settings[date.timestamp] !== 'undefined') {
          return { 'background-image' : 'url(' + self.storage.spring2017campaign.bonuses_settings[date.timestamp].image + ')' };
        }

        return {};
      };

      self.todayInsight = function (timestamp) {
        self.storage.todays_insight_timestamp = timestamp;

        $state.go('main', {tab: 'tab_2', update_mobile: true}, {reload: true});
      };

    });
  };

})(jQuery);
