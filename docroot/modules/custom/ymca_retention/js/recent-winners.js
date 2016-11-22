(function($) {

  Drupal.behaviors.ymca_retention_recent_winners = {};
  Drupal.behaviors.ymca_retention_recent_winners.attach = function (context, settings) {
    $('.ymca-retention-recent-winners', context)
      .once('ymca-retention-recent-winners')
      .cookieBar({
        closeButton : '.btn-close',
        path: settings.path.baseUrl
      });
  };

})(jQuery);
