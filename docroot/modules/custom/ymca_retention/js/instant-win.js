(function($) {

  Drupal.behaviors.ymca_retention_instant_win = {};
  Drupal.behaviors.ymca_retention_instant_win.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-instant-win-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-instant-win-processed');


    Drupal.ymca_retention.angular_app.controller('InstantWinController', function ($timeout, $sce, storage) {
      var self = this;
      // Shared information.
      self.storage = storage;

      self.instantWinStatus = function() {
        return  $sce.trustAsHtml(Drupal.formatPlural(
          self.storage.instantWinCount,
          'You have <span class="title-highlight">1 chance to win</span>',
          'You have <span class="title-highlight">@count chances to win</span>'
        ));
      };

      self.gameWidgetClass = function() {
        var classes = [];
        if (!self.storage.instantWinCount && self.storage.state == 'game') {
          classes.push('disabled');
        }
        return classes.join(' ');
      };

      self.gameWheelClass = function() {
        var classes = [];
        if (self.storage.state == 'process') {
          classes.push('active');
        }
        return classes.join(' ');
      };

      self.testYourLuck = function() {
        self.storage.state = 'process';
        self.storage.getMemberPrize().then(function(data) {
          $timeout(function() {
            self.storage.state = 'result.win';
          }, 3000);
        });
      };
    });
  };

})(jQuery);
