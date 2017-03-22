(function($) {

  Drupal.behaviors.ymca_retention_ngwinners_app = {};
  Drupal.behaviors.ymca_retention_ngwinners_app.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-ngwinners-app-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-ngwinners-app-processed');

    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.ngwinners_app = Drupal.ymca_retention.ngwinners_app || angular.module('RetentionWinners', []);

    Drupal.ymca_retention.ngwinners_app.controller('RetentionWinnersController', ['$scope', function($scope) {
      if (settings.ymca_retention.winnersData.branches.length === 0) {
        return;
      }

      var branches = $.map(settings.ymca_retention.winnersData.branches, function(value, index) {
        return [{'id': index, 'name': value}];
      });
      var selectedBranch = branches[0];
      var winners = settings.ymca_retention.winnersData.winners[selectedBranch.id];
      $scope.branches = branches;
      $scope.selectedBranch = selectedBranch;
      $scope.winners1place = winners[1];
      $scope.winners2place = winners[2];
      $scope.winners3place = winners[3];

      $scope.update = function() {
        var winners = settings.ymca_retention.winnersData.winners[$scope.selectedBranch.id];
        $scope.winners1place = winners[1];
        $scope.winners2place = winners[2];
        $scope.winners3place = winners[3];
      };

    }]);

  };

})(jQuery);
