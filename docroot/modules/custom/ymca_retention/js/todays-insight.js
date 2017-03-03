(function($) {

  Drupal.behaviors.ymca_retention_todays_insight = {};
  Drupal.behaviors.ymca_retention_todays_insight.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-todays-insight-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-todays-insight-processed');

    Drupal.ymca_retention.angular_app.controller('TodaysInsightController', function ($scope, $timeout, $sce, storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      $scope.getArticle = function () {
        return $sce.trustAsHtml(self.storage.todays_insight);
      };

      self.addBonus = function () {
        if (typeof self.storage.spring2017campaign.dates === 'undefined' || !self.storage.spring2017campaign.dates ||
          typeof self.storage.spring2017campaign.bonuses_settings === 'undefined' || !self.storage.spring2017campaign.bonuses_settings) {
          return;
        }

        var currentDay = self.storage.spring2017campaign.dates[self.storage.campaign.days_left - 1];
        currentDay.bonus_code = self.storage.spring2017campaign.bonuses_settings[currentDay.timestamp].bonus_code;
        self.storage.setMemberBonus(currentDay);
      };

      self.isBonus = function () {
        if (typeof self.storage.spring2017campaign.dates === 'undefined' || !self.storage.spring2017campaign.dates ||
          typeof self.storage.member_bonuses === 'undefined' || !self.storage.member_bonuses) {
          return false;
        }

        var currentDay = self.storage.spring2017campaign.dates[self.storage.campaign.days_left - 1];

        return typeof self.storage.member_bonuses[currentDay.timestamp] !== 'undefined';
      };

    });
  };

})(jQuery);
