(function($) {

  Drupal.behaviors.ymca_retention_app = {};
  Drupal.behaviors.ymca_retention_app.attach = function (context, settings) {
    Drupal.ymca_retention = Drupal.ymca_retention || {};
    Drupal.ymca_retention.app = Drupal.ymca_retention.app || angular.module('Retention', []);
  };

})(jQuery);
