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

      $scope.getArticle = function() {
        return $sce.trustAsHtml(self.storage.todays_insight);
      }

    });
  };

})(jQuery);
