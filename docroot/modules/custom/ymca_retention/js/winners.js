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
      $scope.winners_show = settings.ymca_retention.winners.winners_show;

      // Get the data.
      $scope.loadData = function () {
        $scope.winners = settings.ymca_retention.winners.winners[$scope.location.branch_id];
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
