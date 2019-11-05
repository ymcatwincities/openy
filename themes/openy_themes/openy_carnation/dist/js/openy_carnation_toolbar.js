/**
 * @file
 * Additional code that fixes problem of admin_tolbar view and carnation menu on mobile.
 */
(function ($) {
  "use strict";

  /**
   * This makes admin menu toolbar always fixed to the top of the page, even on mobile
   */
  Drupal.behaviors.carnation_toolbar_constrain = {
    attach: function (context, settings) {
      Drupal.toolbar.models.toolbarModel.attributes.isViewportOverflowConstrained = true;
    }
  };

})(jQuery);
