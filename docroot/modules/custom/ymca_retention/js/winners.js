(function($) {

  Drupal.behaviors.ymca_retention_winners = {};
  Drupal.behaviors.ymca_retention_winners.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-winners-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-winners-processed');

    var WinnersModule = angular.module('Winners', []);
    WinnersModule.controller('WinnersController', function ($scope) {
      $scope.locations = settings.ymca_retention.winners.locations;
      $scope.location = $scope.locations[0];

      // Get the data.
      $scope.loadData = function () {
        if ($scope.location.branch_id === 0) {
          $scope.winners = {};
          $scope.winners_show = false;
          return;
        }

        $scope.winners = settings.ymca_retention.winners.winners[$scope.location.branch_id];
        $scope.winners_show = true;
      };
      $scope.loadData();

      // Location change.
      $scope.locationChange = function () {
        $scope.loadData();
      };

    });

    // Bootstrap AngularJS application.
    angular.bootstrap(document.getElementById('winners'), ['Winners']);
  };

})(jQuery);
