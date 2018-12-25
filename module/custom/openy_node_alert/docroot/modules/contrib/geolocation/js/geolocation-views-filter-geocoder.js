/**
 * @file
 *   Javascript for the Google geocoder function, specifically the views filter.
 */

/**
 * @param {String} drupalSettings.geolocation.geocoder.viewsFilterGeocoder
 */

(function ($, Drupal) {
  'use strict';

  /**
   * @namespace
   */
  Drupal.geolocation = Drupal.geolocation || {};
  Drupal.geolocation.geocoder = Drupal.geolocation.geocoder || {};

  /**
   * Attach common map style functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches views geolocation filter geocoder to relevant elements.
   */
  Drupal.behaviors.geolocationViewsFilterGeocoder = {
    attach: function (context) {
      $.each(drupalSettings.geolocation.geocoder.viewsFilterGeocoder, function (elementId, settings) {

        /**
         * @param {GoogleAddress} address - Google address object.
         */
        Drupal.geolocation.geocoder.addResultCallback(function (address) {
          if (typeof address.geometry.location === 'undefined') {
            return false;
          }

          if (typeof address.geometry.viewport === 'undefined') {
            address.geometry.viewport = {
              getNorthEast: function () {
                return {
                  lat: function () {
                    return address.geometry.location.lat();
                  },
                  lng: function () {
                    return address.geometry.location.lng();
                  }
                };
              },
              getSouthWest: function () {
                return {
                  lat: function () {
                    return address.geometry.location.lat();
                  },
                  lng: function () {
                    return address.geometry.location.lng();
                  }
                };
              }
            };
          }

          switch (settings.type) {
            case 'boundary':
              $(context).find("input[name='" + elementId + "[lat_north_east]']").val(address.geometry.viewport.getNorthEast().lat());
              $(context).find("input[name='" + elementId + "[lng_north_east]']").val(address.geometry.viewport.getNorthEast().lng());
              $(context).find("input[name='" + elementId + "[lat_south_west]']").val(address.geometry.viewport.getSouthWest().lat());
              $(context).find("input[name='" + elementId + "[lng_south_west]']").val(address.geometry.viewport.getSouthWest().lng());
              break;

            case 'proximity':
              $(context).find("input[name='" + elementId + "-lat']").val(address.geometry.location.lat());
              $(context).find("input[name='" + elementId + "-lng']").val(address.geometry.location.lng());
              break;
          }
        }, elementId);

        Drupal.geolocation.geocoder.addClearCallback(function () {
          switch (settings.type) {
            case 'boundary':
              $(context).find("input[name='" + elementId + "[lat_north_east]']").val('');
              $(context).find("input[name='" + elementId + "[lng_north_east]']").val('');
              $(context).find("input[name='" + elementId + "[lat_south_west]']").val('');
              $(context).find("input[name='" + elementId + "[lng_south_west]']").val('');
              break;

            case 'proximity':
              $(context).find("input[name='" + elementId + "-lat']").val('');
              $(context).find("input[name='" + elementId + "-lng']").val('');
              break;
          }
        }, elementId);

        delete drupalSettings.geolocation.geocoder.viewsFilterGeocoder[elementId];
      });
    }
  };

})(jQuery, Drupal);
