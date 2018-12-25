(function($) {

  Drupal.behaviors.ymca_retention_todays_insight = {};
  Drupal.behaviors.ymca_retention_todays_insight.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-todays-insight-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-todays-insight-processed');

    Drupal.ymca_retention.angular_app.controller('TodaysInsightController', function ($scope, $sce, storage, $interval) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.addBonus = function () {
        if (typeof self.storage.campaign.dates === 'undefined' || !self.storage.campaign.dates ||
          typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings) {
          return;
        }

        var currentDay = self.storage.campaign.dates[self.storage.campaign.days_left - 1];
        currentDay.bonus_code = self.storage.spring2017campaign.bonuses_settings[currentDay.timestamp].bonus_code;
        self.storage.setMemberBonus(currentDay);
      };

      self.isBonusReceived = function () {
        if (typeof self.storage.campaign.dates === 'undefined' || !self.storage.campaign.dates ||
          typeof self.storage.member_bonuses === 'undefined' || !self.storage.member_bonuses ||
          !self.isTodaysInsightLoaded()) {
          return true;
        }

        var currentDay = self.storage.campaign.dates[self.storage.campaign.days_left - 1];

        if (currentDay.timestamp != self.storage.todays_insight_timestamp) {
          return true;
        }

        return !angular.isUndefined(self.storage.member_bonuses[currentDay.timestamp]);
      };

      self.isTodaysInsightLoaded = function () {
        return self.storage.todays_insight_timestamp &&
          !angular.isUndefined(self.storage.spring2017campaign.today_insights) &&
          !angular.isUndefined(self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp]);
      };

      self.getTitle = function () {
        if (!self.isTodaysInsightLoaded()) {
          return '';
        }

        return self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].title;
      };

      self.getContent = function () {
        if (!self.isTodaysInsightLoaded()) {
          return '';
        }

        return $sce.trustAsHtml(self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].content);
      };

      self.getImage = function () {
        if (!self.isTodaysInsightLoaded()) {
          return '';
        }

        return self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].image;
      };

      self.getTip = function () {
        if (!self.isTodaysInsightLoaded()) {
          return '';
        }

        return self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].tip;
      };

      self.getDate = function () {
        if (angular.isUndefined(self.storage.campaign.dates)) {
          return '';
        }

        var id = self.storage.timestamp_ids[self.storage.todays_insight_timestamp];
        var currentDay = self.storage.campaign.dates[id];
        return currentDay.month + ' ' + currentDay.month_day;
      };

      self.isBonusTimerVisible = function () {
        if (self.isBonusReceived()) {
          return false;
        }

        return self.storage.todays_insight_timer > 0;
      };

      self.isBonusButtonVisible = function () {
        return !self.isBonusReceived() && !self.isBonusTimerVisible();
      };

      self.getVideo = function () {
        if (!self.isTodaysInsightLoaded()) {
          return '';
        }

        return $sce.trustAsHtml(self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].video);
      };

      self.isVideoPresent = function () {
        if (!self.isTodaysInsightLoaded()) {
          return false;
        }

        return self.storage.spring2017campaign.today_insights[self.storage.todays_insight_timestamp].video.length > 0;
      };

      self.previous = function () {
        if (!self.isTodaysInsightLoaded()) {
          return;
        }
        var id = self.storage.timestamp_ids[self.storage.todays_insight_timestamp];
        self.storage.todays_insight_timestamp = self.storage.campaign.dates[id - 1].timestamp;
      };

      self.next = function () {
        if (!self.isTodaysInsightLoaded()) {
          return;
        }
        var id = self.storage.timestamp_ids[self.storage.todays_insight_timestamp];
        self.storage.todays_insight_timestamp = self.storage.campaign.dates[id + 1].timestamp;
      };

      self.isPrevious = function () {
        if (!self.isTodaysInsightLoaded()) {
          return;
        }
        var id = self.storage.timestamp_ids[self.storage.todays_insight_timestamp];

        if (angular.isUndefined(self.storage.campaign.dates[id - 1])) {
          return false;
        }

        var previousDay = self.storage.campaign.dates[id - 1].timestamp;

        return !angular.isUndefined(self.storage.spring2017campaign.today_insights[previousDay]);
      };

      self.isNext = function () {
        if (!self.isTodaysInsightLoaded()) {
          return;
        }
        var id = self.storage.timestamp_ids[self.storage.todays_insight_timestamp];

        if (angular.isUndefined(self.storage.campaign.dates[id + 1])) {
          return false;
        }

        var nextDay = self.storage.campaign.dates[id + 1].timestamp;

        return !angular.isUndefined(self.storage.spring2017campaign.today_insights[nextDay]);
      };

      $interval(function() {
        if (self.storage.todays_insight_timer > 0 &&  $('#tab_2').is(':visible') && !self.isBonusReceived()) {
          self.storage.todays_insight_timer--;
        }
      }, 1000);

    });
  };

})(jQuery);
