(function($) {

  Drupal.behaviors.ymca_retention_instant_win = {};
  Drupal.behaviors.ymca_retention_instant_win.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-instant-win-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-instant-win-processed');


    Drupal.ymca_retention.angular_app.controller('InstantWinController', function ($filter, $timeout, $sce, storage) {
      var self = this;
      // Shared information.
      self.storage = storage;
      // Prize value.
      self.value = 0;

      self.instantWinStatus = function() {
        return $sce.trustAsHtml(Drupal.formatPlural(
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

      self.resetGame = function() {
        self.storage.getMemberChances().then(function() {
          self.storage.state = 'game';
        });
      };

      self.testYourLuck = function() {
        self.storage.state = 'process';
        self.storage.getMemberPrize().then(function(data) {
          $timeout(function() {
            var last_played_chance = self.storage.calculateLastPlayedChance(data);

            if (
              typeof last_played_chance === 'undefined' ||
              (
                typeof self.storage.last_played_chance !== 'undefined' &&
                self.storage.last_played_chance.id === last_played_chance.id
              )
            ) {
              // It seems this is not an actual play. Let's reset game
              // without any messages.
              self.resetGame();
              return;
            }

            if (last_played_chance.winner === '1') {
              self.storage.state = 'result.win';
              self.value = last_played_chance.value;
            }
            else {
              self.storage.state = 'result.loss';

              var part_1 = self.storage.loss_messages.part_1;
              var part_2 = self.storage.loss_messages.part_2;

              self.storage.loss_message =
                Drupal.t(part_1[Math.floor(Math.random() * part_1.length)]) + ' ' +
                Drupal.t(part_2[Math.floor(Math.random() * part_2.length)]);
            }
          }, 3000);
        });
      };

      self.lossMessage = function() {
        return $sce.trustAsHtml(self.storage.loss_message);
      };

      self.cardsWonStatus = function() {
        var message = $sce.trustAsHtml('<p>' + Drupal.t("You didn't win any cards yet.") + '</p>');
        if (typeof self.storage.member_chances === 'undefined' || !self.storage.member_chances) {
          return message;
        }

        var count = $filter('filter')(self.storage.member_chances, {'played': '!0', 'winner': '1'}, true).length;
        if (!count) {
          return message;
        }

        message = $sce.trustAsHtml('<p>' + Drupal.formatPlural(
            count,
            'You have won 1 card so far.',
            'You have won @count cards so far.'
          ) + '</p>' + '<p>Please check your email for further instructions.</p>');

        return message;
      };

      self.gameHistoryMessageClass = function(chance) {
        var classes = [];
        if (chance.winner === '1') {
          classes.push('active');
        }
        return classes.join(' ');
      };
    });
  };

})(jQuery);
