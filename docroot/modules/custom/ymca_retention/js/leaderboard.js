(function($) {

  Drupal.behaviors.ymca_retention_leaderboard = {};
  Drupal.behaviors.ymca_retention_leaderboard.attach = function(context, settings) {
    if ($('body').hasClass('ymca-retention-leaderboard-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-leaderboard-processed');

    var LeaderboardModule = angular.module('Leaderboard', ['ajoslin.promise-tracker']);
    LeaderboardModule.controller('LeaderboardController', function($scope, $http, promiseTracker) {
      // Initiate the promise tracker to track requests.
      $scope.progress = promiseTracker();

      $scope.locations = settings.ymca_retention.leaderboard.locations;
      $scope.location = $scope.locations[0];

      // Get the data.
      $scope.loadData = function() {
        $scope.quantity = 20;
        $scope.order = 'visits';
        if ($scope.location.branch_id === 0) {
          $scope.members = [];
          return;
        }

        var $promise = $http({
          method: 'GET',
          url: settings.ymca_retention.leaderboard.leaderboard_url_pattern.replace('0000', $scope.location.branch_id),
          cache: true
        })
          .then(function(response) {
            $scope.members = response.data;
          });

        // Track the request and show its progress to the user.
        $scope.progress.addPromise($promise);
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

      $scope.byString = function(o, s) {
        // Convert indexes to properties.
        s = s.replace(/\[(\w+)\]/g, '.$1');
        // Strip a leading dot.
        s = s.replace(/^\./, '');
        var a = s.split('.');
        for (var i = 0, n = a.length; i < n; ++i) {
          var k = a[i];
          if (k in o) {
            o = o[k];
          }
          else {
            return;
          }
        }

        return o;
      };
      $scope.memberClass = function(index) {
        var classes = [];
        var member_points = $scope.byString($scope.members_sorted[index], $scope.order),
          third_place_points = $scope.byString($scope.members_sorted[2], $scope.order);
        if (index <= 2 || member_points === third_place_points) {
          classes.push('leader');
          if (index + 1 < $scope.members_sorted.length) {
            var next_member_points = $scope.byString($scope.members_sorted[index + 1], $scope.order);
            if (next_member_points < member_points) {
              classes.push('leader--last');
            }
          }
          else {
            classes.push('leader--last');
          }
        }
        else {
          classes.push('chaser');
          var previous_member_points = $scope.byString($scope.members_sorted[index - 1], $scope.order);
          if (previous_member_points > member_points) {
            classes.push('chaser--first');
          }
        }

        return classes.join(' ');
      };
    });

    // Bootstrap AngularJS application.
    angular.bootstrap(document.getElementById('leaderboard-app'), ['Leaderboard']);
  };

})(jQuery);
