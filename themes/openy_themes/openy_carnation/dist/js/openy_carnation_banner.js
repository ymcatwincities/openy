/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * Move Header Banner paragraph to header.
   */
  Drupal.behaviors.openyMoveBanners = {
    attach: function (context, settings) {
      var bannerHeader = $('.paragraph--type--banner, .landing-header, .page-heading');
      if (bannerHeader.length > 0) {
        $('.banner-zone-node').once('openy-move-banners').append(bannerHeader.eq(0));
      }
    }
  };

  /**
   * Ensure header alerts are after breadcrumbs in the DOM
   */
  Drupal.behaviors.openyMoveHeaderAlerts = {
    attach: function (context, settings) {
      var headerAlerts = $('.block-openy-carnation-views-block-alerts-header-alerts', context);
      var subHeaderFilters = $('.banner-cta', context);
      if (headerAlerts.length && subHeaderFilters.length) {
        headerAlerts.once('openy-move-alerts').insertAfter(subHeaderFilters);
      }
    }
  };
})(jQuery);
