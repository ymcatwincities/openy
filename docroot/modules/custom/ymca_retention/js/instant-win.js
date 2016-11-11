(function($) {

  Drupal.behaviors.ymca_retention_instant_win = {};
  Drupal.behaviors.ymca_retention_instant_win.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-instant-win-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-instant-win-processed');


    Drupal.ymca_retention.angular_app.controller('InstantWinController', function ($scope, $cookies, promiseTracker, courier) {
      // Initiate the promise tracker to track submissions.
      $scope.progress = promiseTracker();

      // Watch cookie value and update member chances data on change.
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, function (newVal, oldVal) {
        $scope.getMemberChances(newVal);
      });
      $scope.getMemberChances = function(id) {
        courier.getMemberChances(id).then(function(data) {
          $scope.member_chances = data;
        });
      };
    });
  };

})(jQuery);
