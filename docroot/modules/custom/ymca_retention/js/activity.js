(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-activity-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-activity-processed');

    Drupal.ymca_retention.angular_app.controller('ActivityController', function (storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.dates = settings.ymca_retention.activity.dates;
      self.activity_groups = settings.ymca_retention.activity.activity_groups;
      self.date_index = -1;
      self.dates.forEach(function (item, i, arr) {
        if (item.past) {
          self.date_index = i;
        }
      });
      self.date_selected = this.dates[this.date_index];

      self.dateClass = function (index) {
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
      self.activitiesCount = function (index) {
        if (typeof self.storage.member_activities === 'undefined') {
          return 0;
        }

        var count = 0;
        for (var activity in self.storage.member_activities[self.dates[index].timestamp]) {
          if (self.storage.member_activities[self.dates[index].timestamp][activity]) {
            count++;
          }
        }
        return count;
      };

      self.activity_group_index = 0;
      self.activityGroupClass = function (index) {
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
      self.activityGroupSet = function (index) {
        self.activity_group_index = index;
      };
      self.activityGroupShow = function (index) {
        return self.activity_group_index === index;
      };

      // Track the last clicked activity id.
      self.last_activity_id = -1;
      self.activityItemChange = function (id) {
        self.last_activity_id = id;
        var timestamp = self.date_selected.timestamp;
        var data = {
          'timestamp': timestamp,
          'id': id,
          'value': self.storage.member_activities[timestamp][id]
        };

        self.storage.setMemberActivities(data);
      };
      self.activityItemClass = function (id) {
        var classes = [];
        if (id === self.last_activity_id) {
          classes.push('activity--click-last');
        }

        return classes.join(' ');
      };
    });
  };

})(jQuery);
