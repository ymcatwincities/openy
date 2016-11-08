(function($) {

  Drupal.behaviors.ymca_retention_angular_app = {};
  Drupal.behaviors.ymca_retention_angular_app.attach = function (context, settings) {
    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.angular_app = Drupal.ymca_retention.angular_app || angular.module('Retention', []);

    Drupal.ymca_retention.angular_app.controller('RetentionController', function () {
      this.user = {
        firstName: 'Andrew'
      };
    });
  };

})(jQuery);
