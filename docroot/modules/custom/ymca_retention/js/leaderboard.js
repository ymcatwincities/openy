(function($) {

  Drupal.behaviors.ymca_retention_leaderboard = {};
  Drupal.behaviors.ymca_retention_leaderboard.attach = function(context, settings) {
    if ($('body').hasClass('ymca-retention-leaderboard-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-leaderboard-processed');

    var LeaderboardModule = angular.module('Leaderboard', []);
    LeaderboardModule.controller('LeaderboardController', function($scope, $http, $interval) {
      $scope.locations = [
        {branch_id: -1, name: 'Select location...'},
        {branch_id: 14, name: 'Location 14'},
        {branch_id: 26, name: 'Location 26'}
      ];
      $scope.location = $scope.locations[0];

      // Get the data.
      $scope.loadData = function() {
        $scope.quantity = 20;
        $scope.order = 'visits';
        if ($scope.location.branch_id === -1) {
          $scope.members = [];
        }
        else {
          $http.get(settings.ymca_retention.leaderboard.replace('branch_id', $scope.location.branch_id)).success(function(data) {
            $scope.members = data;
          });
        }
      };
      $scope.loadData();

      // Sorting.
      $scope.order = 'visits';
      $scope.reverse = true;
      $scope.sort = function(order) {
        $scope.order = order;
      };

      $scope.headerClass = function(order) {
        return $scope.order === order ? 'active' : '';
      };

      // Location change.
      $scope.locationChange = function() {
        $scope.loadData();
      };

      // Load more members.
      $scope.loadMore = function() {
        if ($scope.quantity < $scope.members.length) {
          $scope.quantity = $scope.quantity + 20;
        }
      };
    });

    // Bootstrap AngularJS application.
    angular.bootstrap(document.getElementById('leaderboard-app'), ['Leaderboard']);
  };

})(jQuery);
