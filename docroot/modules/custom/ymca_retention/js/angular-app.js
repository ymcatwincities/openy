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
        fetcher.getMemberData(id).then(angular.bind(this, function(data) {
          this.member = data;
        }));
      });

      this.cookieRemove = function() {
        $cookies.remove('Drupal.visitor.ymca_retention_member', { path: '/' });
      };

    });

    // Service to communicate with backend.
    Drupal.ymca_retention.angular_app.factory('fetcher', function($http, $q) {
      function getMemberData(id) {
        var deferred = $q.defer();
        if (typeof id === 'undefined') {
          deferred.resolve(null);
        }
        else {
          $http.get(settings.ymca_retention.user_menu.member_url).then(function(response) {
            if ($.isEmptyObject(response.data)) {
              deferred.resolve(null);
              return;
            }

            deferred.resolve(response.data);
          });
        }

        return deferred.promise;
      }

      return {
        getMemberData: getMemberData
      };
    });
  };

})(jQuery);
