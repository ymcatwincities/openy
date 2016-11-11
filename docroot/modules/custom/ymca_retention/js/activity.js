(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-activity-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-activity-processed');

    Drupal.ymca_retention.angular_app.controller('ActivityController', function ($scope, $cookies, promiseTracker, courier, storage) {
      var self = this;
      // Shared information.
      this.storage = storage;
      // Initiate the promise tracker to track submissions.
      this.progress = promiseTracker();

      // Watch cookie value and update member activities data on change.
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, function (newVal, oldVal) {
        self.getMemberActivities(newVal);
        self.date_selected = self.dates[self.date_index];
      });
      this.getMemberActivities = function(id) {
        courier.getMemberActivities(id).then(function(data) {
          self.member_activities = data;
        });
      };
      this.setMemberActivities = function(data) {
        var $promise = courier.setMemberActivities(data).then(function(data) {
          // self.member_activities = data;
          storage.getMemberChances();
        });

        // Track the request and show its progress to the user.
        self.progress.addPromise($promise);
      };

      this.dates = settings.ymca_retention.activity.dates;
      this.activity_groups = settings.ymca_retention.activity.activity_groups;
      this.date_index = -1;
      this.dates.forEach(function (item, i, arr) {
        if (item.past) {
          self.date_index = i;
        }
      });
      this.date_selected = this.dates[this.date_index];

      this.dateClass = function (index) {
        var classes = [];
        if (self.dates[index].past) {
          classes.push('campaign-dates--date-past');
        }
        if (self.date_index === index) {
          classes.push('campaign-dates--date-current');
        }
        if (self.dates[index].future) {
          classes.push('campaign-dates--date-future');
        }

        if (!self.activitiesCount(index)) {
          classes.push('campaign-dates--date-no-activity');
        }

        return classes.join(' ');
      };
      this.activitiesCount = function (index) {
        if (typeof self.member_activities === 'undefined') {
          return 0;
        }

        var count = 0;
        for (var activity in self.member_activities[self.dates[index].timestamp]) {
          if (self.member_activities[self.dates[index].timestamp][activity]) {
            count++;
          }
        }
        return count;
      };

      this.activity_group_index = 0;
      this.activityGroupClass = function (index) {
        var classes = [];
        if (self.activity_groups[index].name === 'Swim') {
          classes.push('activity-tab__type-a');
        }
        if (self.activity_groups[index].name === 'Fitness') {
          classes.push('activity-tab__type-b');
        }
        if (self.activity_groups[index].name === 'Free Group X') {
          classes.push('activity-tab__type-c');
        }
        if (self.activity_groups[index].name === 'Community') {
          classes.push('activity-tab__type-d');
        }
        if (self.activity_group_index === index) {
          classes.push('active');
        }

        return classes.join(' ');
      };
      this.activityGroupSet = function (index) {
        self.activity_group_index = index;
      };
      this.activityGroupShow = function (index) {
        return self.activity_group_index === index;
      };

      // Track the last clicked activity id.
      this.last_activity_id = -1;
      this.activityItemChange = function (id) {
        self.last_activity_id = id;
        var timestamp = self.date_selected.timestamp;
        var data = {
          'timestamp': timestamp,
          'id': id,
          'value': self.member_activities[timestamp][id]
        };

        self.setMemberActivities(data);
      };
      this.activityItemClass = function (id) {
        var classes = [];
        if (id === self.last_activity_id) {
          classes.push('activity--click-last');
        }

        return classes.join(' ');
      };
    });
  };

})(jQuery);
