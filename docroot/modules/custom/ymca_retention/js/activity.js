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

      self.current_date_index = -1;

      if (typeof self.storage.dates === 'undefined') {
        console.log('"self.storage.dates" is undefined.');
        return;
      }
      self.storage.dates.forEach(function (item, i, arr) {
        if (item.past) {
          self.current_date_index = i;
        }
      });
      self.date_selected = self.storage.dates[self.current_date_index];
      self.dateClass = function (index) {
        var classes = [];
        if (self.storage.dates[index].past) {
          classes.push('campaign-dates--date-past');
        }
        if (self.date_selected.index === index) {
          classes.push('campaign-dates--date-current');
        }
        if (self.storage.dates[index].future) {
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
        for (var activity in self.storage.member_activities[self.storage.dates[index].timestamp]) {
          if (self.storage.member_activities[self.storage.dates[index].timestamp][activity]) {
            count++;
          }
        }
        return count;
      };

      self.activity_group_index = 0;
      self.activityGroupClass = function (index, activity_group) {
        var classes = [];
        classes.push('activity-tab__type-' + activity_group.retention_activity_id);

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
