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

      self.activityGroupClass = function (index) {
        var classes = [];
        if (self.storage.activity_groups[index].name === 'Swim') {
          classes.push('item-activity-type__a');
        }
        if (self.storage.activity_groups[index].name === 'Fitness') {
          classes.push('item-activity-type__b');
        }
        if (self.storage.activity_groups[index].name === 'Free Group X') {
          classes.push('item-activity-type__c');
        }
        if (self.storage.activity_groups[index].name === 'Community') {
          classes.push('item-activity-type__d');
        }

        return classes.join(' ');
      };
    });
  };

})(jQuery);
