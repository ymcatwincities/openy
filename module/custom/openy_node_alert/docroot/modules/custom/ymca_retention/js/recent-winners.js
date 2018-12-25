(function($) {

  Drupal.behaviors.ymca_retention_recent_winners = {};
  Drupal.behaviors.ymca_retention_recent_winners.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-recent-winners-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-recent-winners-processed');

    Drupal.ymca_retention.angular_app.controller('RecentWinnersController', function (storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.storage.getRecentWinners();
    });

    $('.ymca-retention-recent-winners', context)
      .cookieBar({
        closeButton : '.btn-close',
        path: settings.path.baseUrl
      });
  };

})(jQuery);
