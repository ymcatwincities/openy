(function($) {

  Drupal.behaviors.ymca_retention_angular_app = {};
  Drupal.behaviors.ymca_retention_angular_app.attach = function (context, settings) {
    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.angular_app = Drupal.ymca_retention.angular_app || angular.module('Retention', ['ngCookies']);

    Drupal.ymca_retention.angular_app.controller('RetentionController', function ($scope, $log, $cookies, $timeout, $interval, fetcher) {
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, angular.bind(this, function (newVal, oldVal) {
        this.getMemberData(newVal);
      }));
      // Force to check cookie value.
      $interval(function() {
        $cookies.get('Drupal.visitor.ymca_retention_member');
      }, 500);

      this.getMemberData = angular.bind(this, function(id) {
        if (typeof id === 'undefined') {
          this.member = '';
          return;
        }

        fetcher.getMemberData(id).then(angular.bind(this, function(response) {
          this.member = response.data;
        }));
      });

      this.cookieRemove = function() {
        $cookies.remove('Drupal.visitor.ymca_retention_member', { path: '/' });
      };

    });

    // Service to communicate with backend.
    Drupal.ymca_retention.angular_app.factory('fetcher', function($http) {
      return {
        getMemberData: function(id) {
          return $http.get(settings.ymca_retention.user_menu.member_url);
        }
      };
    });
  };

})(jQuery);
