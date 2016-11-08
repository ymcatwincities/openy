(function($) {

  Drupal.behaviors.ymca_retention_angular_app = {};
  Drupal.behaviors.ymca_retention_angular_app.attach = function (context, settings) {
    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.angular_app = Drupal.ymca_retention.angular_app || angular.module('Retention', ['ngCookies']);

    Drupal.ymca_retention.angular_app.controller('RetentionController', function ($scope, $log, $cookies, $timeout, $interval, fetcher) {
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, angular.bind(this, function (newVal, oldVal) {
        this.getUserData(newVal);
      }));
      // Force to check cookie value.
      $interval(function() {
        $cookies.get('Drupal.visitor.ymca_retention_member');
      }, 500);

      this.getUserData = function(id) {
        this.user = fetcher.getUserData(id);
      };

      this.cookieRemove = function() {
        $cookies.remove('Drupal.visitor.ymca_retention_member');
      };

    });

    // Service to communicate with backend.
    Drupal.ymca_retention.angular_app.factory('fetcher', function() {
      return {
        getUserData: function(id) {
          if (typeof id != 'undefined') {
            return {
              firstName: 'Andrew'
            };
          }
          else {
            return '';
          }
        }
      };
    });
  };

})(jQuery);
