(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-activity-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-activity-processed');

    Drupal.ymca_retention.angular_app.controller('ActivityController', function ($scope, $http, $log, promiseTracker, $timeout, $httpParamSerializerJQLike) {
      // Initiate the promise tracker to track submissions.
      $scope.progress = promiseTracker();

      $scope.dates = settings.ymca_retention.activity.dates;
      $scope.date_index = -1;
      $scope.dates.forEach(function (item, i, arr) {
        if (item.past) {
          $scope.date_index = i;
        }
      });
      $scope.date_selected = $scope.dates[$scope.date_index];

      // Track the last clicked activity id.
      $scope.last_activity_id = -1;

      $scope.activity_groups = settings.ymca_retention.activity.activity_groups;
      $scope.activity_group_index = 0;

      $http({
        method: 'GET',
        url: settings.ymca_retention.activity.member_activities
      })
        .then(function (response) {
          $scope.member_activities = response.data;
        });

      $scope.dateClass = function (index) {
        var classes = [];
        if ($scope.dates[index].past) {
          classes.push('campaign-dates--date-past');
        }
        if ($scope.date_index === index) {
          classes.push('campaign-dates--date-current');
        }
        if ($scope.dates[index].future) {
          classes.push('campaign-dates--date-future');
        }

        if (!$scope.activitiesCount(index)) {
          classes.push('campaign-dates--date-no-activity');
        }

        return classes.join(' ');
      };

      $scope.setDate = function (index) {
        if ($scope.dates[index].past) {
          $scope.date_index = index;
        }
      };

      $scope.activityGroupClass = function (index) {
        var classes = [];
        if ($scope.activity_groups[index].name === 'Swim') {
          classes.push('activity-tab__type-a');
        }
        if ($scope.activity_groups[index].name === 'Fitness') {
          classes.push('activity-tab__type-b');
        }
        if ($scope.activity_groups[index].name === 'Free Group X') {
          classes.push('activity-tab__type-c');
        }
        if ($scope.activity_groups[index].name === 'Community') {
          classes.push('activity-tab__type-d');
        }
        if ($scope.activity_group_index === index) {
          classes.push('active');
        }

        return classes.join(' ');
      };

      $scope.setActivityGroup = function (index) {
        $scope.activity_group_index = index;
      };

      $scope.activityItemsShow = function (index) {
        return $scope.activity_group_index === index;
      };

      $scope.activitiesCount = function (index) {
        if (typeof $scope.member_activities === 'undefined') {
          return 0;
        }

        var count = 0;
        for (var activity in $scope.member_activities[$scope.dates[index].timestamp]) {
          if ($scope.member_activities[$scope.dates[index].timestamp][activity]) {
            count++;
          }
        }
        return count;
      };

      $scope.activityItemChange = function (id) {
        $scope.last_activity_id = id;
        var timestamp = $scope.dates[$scope.date_index].timestamp;
        var data = {
          'timestamp': timestamp,
          'id': id,
          'value': $scope.member_activities[timestamp][id]
        };

        var $promise = $http({
          method: 'POST',
          url: settings.ymca_retention.activity.member_activities,
          data: $httpParamSerializerJQLike(data),
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
          .then(function (response) {
            // $scope.member_activities = response.data;
          });

        // Track the request and show its progress to the user.
        $scope.progress.addPromise($promise);
      };

      $scope.activityItemClass = function (id) {
        var classes = [];
        if (id === $scope.last_activity_id) {
          classes.push('activity--click-last');
        }

        return classes.join(' ');
      };
    });
  };

})(jQuery);
