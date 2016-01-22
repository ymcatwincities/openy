/**
 * @file
 * Enable/disable menu items.
 */

(function ($) {

  "use strict";

  /**
   * Control visibility of personify menu items.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.personify = {
    attach: function(context, settings) {
      var cookie = $.cookie('Drupal.visitor.personify_authorized');
      if (cookie !== undefined) {
        $('#block-topmenu ul li a', context).filter('a[href="/personify/login"]').hide();
      }
      else {
        $('#block-topmenu ul li a', context).filter('a[href="/personify/account"]').hide();
      }
    }
  };

})(jQuery);
