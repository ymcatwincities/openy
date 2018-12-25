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
      if (cookie) {
        $('.nav-global a[href$="/personify/login"]', context).parent('li').hide();
      }
      else {
        $('.nav-global a[href$="/personify/account"]', context).parents('li.dropdown').hide();
      }
    }
  };

})(jQuery);
