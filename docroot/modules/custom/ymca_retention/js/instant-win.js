(function($) {

  Drupal.behaviors.ymca_retention_instant_win = {};
  Drupal.behaviors.ymca_retention_instant_win.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-instant-win-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-instant-win-processed');


    Drupal.ymca_retention.angular_app.controller('InstantWinController', function (promiseTracker, storage) {
      // Initiate the promise tracker to track submissions.
      this.progress = promiseTracker();

      this.storage = storage;
    });
  };

})(jQuery);
