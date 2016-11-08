(function($) {

  Drupal.behaviors.ymca_retention_angular_app = {};
  Drupal.behaviors.ymca_retention_angular_app.attach = function (context, settings) {
    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.angular_app = Drupal.ymca_retention.angular_app || angular.module('Retention', ['ngCookies']);

    Drupal.ymca_retention.angular_app.controller('RetentionController', function ($scope, $log, $cookies, $timeout, $interval) {
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, angular.bind(this, function (newVal, oldVal) {
        this.getUserData(newVal);
      }));
      // Force to check cookie value every second.
      $interval(function() {
        $cookies.get('Drupal.visitor.ymca_retention_member');
      }, 1000);

      this.getUserData = function(value) {
        if (typeof value != 'undefined') {
          this.user = {
            firstName: 'Andrew'
          };
        }
        else {
          this.user = '';
        }
      };

      this.cookieRemove = function() {
        $cookies.remove('Drupal.visitor.ymca_retention_member');
      };

    });
  };

})(jQuery);
