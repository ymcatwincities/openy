(function ($) {

  Drupal.behaviors.ymca_retention_progress = {};
  Drupal.behaviors.ymca_retention_progress.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-progress-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-progress-processed');

    Drupal.ymca_retention.angular_app.controller('ProgressController', function (storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.dateClass = function (date) {
        var classes = [];
        if (date.future) {
          classes.push('progress-row__disabled');
        }
        return classes.join(' ');
      };

      self.activityGroupClass = function (timestamp, activity_group) {
        var classes = [];
        if (activity_group.name === 'Swim') {
          classes.push('item-activity-type__a');
        }
        if (activity_group.name === 'Fitness') {
          classes.push('item-activity-type__b');
        }
        if (activity_group.name === 'Free Group X') {
          classes.push('item-activity-type__c');
        }
        if (activity_group.name === 'Community') {
          classes.push('item-activity-type__d');
        }

        if (self.activitiesCount(timestamp, activity_group)) {
          classes.push('active');
        }

        return classes.join(' ');
      };

      self.activitiesCount = function(timestamp, activity_group) {
        if (typeof self.storage.member_activities === 'undefined') {
          return 0;
        }

        var count = 0;
        for (var activity in activity_group.activities) {
          if (self.storage.member_activities[timestamp][activity_group.activities[activity].id]) {
            count++;
          }
        }

        return count;
      };
    });
  };

})(jQuery);
