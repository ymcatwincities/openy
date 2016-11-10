(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-activity-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-activity-processed');

    Drupal.ymca_retention.angular_app.controller('ActivityController', function ($scope, $cookies, promiseTracker, courier) {
      // Initiate the promise tracker to track submissions.
      $scope.progress = promiseTracker();

      // Watch cookie value and update member activities data on change.
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, function (newVal, oldVal) {
        $scope.getMemberActivities(newVal);
        $scope.date_selected = $scope.dates[$scope.date_index];
      });
      $scope.getMemberActivities = function(id) {
        courier.getMemberActivities(id).then(function(data) {
          $scope.member_activities = data;
        });
      };
      $scope.setMemberActivities = function(data) {
        var $promise = courier.setMemberActivities(data).then(function(data) {
          $scope.member_activities = data;
        });

        // Track the request and show its progress to the user.
        $scope.progress.addPromise($promise);
      };

      $scope.dates = settings.ymca_retention.activity.dates;
      $scope.activity_groups = settings.ymca_retention.activity.activity_groups;
      $scope.date_index = -1;
      $scope.dates.forEach(function (item, i, arr) {
        if (item.past) {
          $scope.date_index = i;
        }
      });
      $scope.date_selected = $scope.dates[$scope.date_index];

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

      $scope.activity_group_index = 0;
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
      $scope.activityGroupSet = function (index) {
        $scope.activity_group_index = index;
      };
      $scope.activityGroupShow = function (index) {
        return $scope.activity_group_index === index;
      };

      // Track the last clicked activity id.
      $scope.last_activity_id = -1;
      $scope.activityItemChange = function (id) {
        $scope.last_activity_id = id;
        var timestamp = $scope.date_selected.timestamp;
        var data = {
          'timestamp': timestamp,
          'id': id,
          'value': $scope.member_activities[timestamp][id]
        };

        $scope.setMemberActivities(data);
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
