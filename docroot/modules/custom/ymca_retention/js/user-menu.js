(function($) {

  Drupal.behaviors.ymca_retention_user_menu = {};
  Drupal.behaviors.ymca_retention_user_menu.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-user-menu-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-user-menu-processed');

    Drupal.ymca_retention.angular_app.controller('UserMenuController', function ($scope, $cookies, member_data) {
      // Watch cookie value and update user data on change.
      $scope.$watch(function () {
        return $cookies.get('Drupal.visitor.ymca_retention_member');
      }, angular.bind(this, function (newVal, oldVal) {
        this.getMemberData(newVal);
      }));

      this.getMemberData = angular.bind(this, function(id) {
        member_data.get(id).then(angular.bind(this, function(data) {
          this.member = data;
        }));
      });

    });

    // Service to communicate with backend.
    Drupal.ymca_retention.angular_app.factory('member_data', function($http, $q) {
      function get(id) {
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
        get: get
      };
    });

    // Populate modal content depending on the clicked link.
    $('#ymca-retention-modal', context)
      .on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget),
          type = $button.data('type'),
          $modal = $(this),
          $modal_body = $modal.find('.modal-body');

        // Assign modal title.
        $modal.find('.modal-title').text($button.text());

        // Check for already existing content and save it.
        if ($.trim($modal_body.html())) {
          $modal_body.find('.ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
        }

        // Add requested form to the modal body.
        if (type === 'register') {
          $('#ymca-retention-user-menu-register-form').appendTo($modal_body);
        }
        else if (type === 'login') {
          $('#ymca-retention-user-menu-login-form').appendTo($modal_body);
        }
      }).
      on('hidden.bs.modal', function (event) {
        var $modal = $(this);
        // Save the form back.
        $modal.find('.modal-body .ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
      });
  };

})(jQuery);
