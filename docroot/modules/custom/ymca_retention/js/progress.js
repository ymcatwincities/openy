(function ($) {

  Drupal.behaviors.ymca_retention_activity = {};
  Drupal.behaviors.ymca_retention_activity.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-progress-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-progress-processed');

    Drupal.ymca_retention.angular_app.controller('ProgressController', function (storage) {
      var self = this;
      // Shared information.
      self.storage = storage;
    });
  };

})(jQuery);
