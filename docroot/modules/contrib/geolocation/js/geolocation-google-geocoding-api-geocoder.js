/**
 * @file
 *   Javascript for the Google Geocoding API geocoder.
 */

/**
 * @property {Object} drupalSettings.geolocation.geocoder.googleGeocodingAPI.components
 */

/**
 * @property {Object} GoogleMap.GeocoderStatus
 * @property {String} GoogleMap.GeocoderStatus.OK
 *
 * @property {function():Object} GoogleMap.Geocoder
 * @property {Function} GoogleMap.Geocoder.geocode
 */

(function ($, Drupal) {
  'use strict';

  /* global google */

  if (typeof Drupal.geolocation.geocoder === 'undefined') {
    return false;
  }

  drupalSettings.geolocation.geocoder.googleGeocodingAPI = drupalSettings.geolocation.geocoder.googleGeocodingAPI || {};

  Drupal.geolocation.geocoder.googleGeocodingAPI = {};

  /**
   * @param {HTMLElement} context Context.
   */
  Drupal.geolocation.geocoder.googleGeocodingAPI.attach = function (context) {
    $('input.geolocation-geocoder-google-geocoding-api', context).once().autocomplete({
      autoFocus: true,
      source: function (request, response) {

        if (typeof Drupal.geolocation.geocoder.googleGeocodingAPI.geocoder === 'undefined') {
          Drupal.geolocation.geocoder.googleGeocodingAPI.geocoder = new google.maps.Geocoder();
        }

        var autocompleteResults = [];
        var componentRestrictions = {};
        if (typeof drupalSettings.geolocation.geocoder.googleGeocodingAPI.components !== 'undefined') {
          componentRestrictions = drupalSettings.geolocation.geocoder.googleGeocodingAPI.components;
        }

        Drupal.geolocation.geocoder.googleGeocodingAPI.geocoder.geocode(
          {
            address: request.term,
            componentRestrictions: componentRestrictions
          },

          /**
           * Google Geocoding API geocode.
           *
           * @param {GoogleAddress[]} results - Returned results
           * @param {String} status - Whether geocoding was successful
           */
          function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
              $.each(results, function (index, result) {
                autocompleteResults.push({
                  value: result.formatted_address,
                  address: result
                });
              });
            }
            response(autocompleteResults);
          }
        );
      },

      /**
       * Option form autocomplete selected.
       *
       * @param {Object} event - See jquery doc
       * @param {Object} ui - See jquery doc
       * @param {Object} ui.item - See jquery doc
       */
      select: function (event, ui) {
        Drupal.geolocation.geocoder.resultCallback(ui.item.address, $(event.target).data('source-identifier'));
        $('.geolocation-geocoder-google-geocoding-api-state[data-source-identifier="' + $(event.target).data('source-identifier') + '"]').val(1);
      }
    })
    .on('input', function () {
      $('.geolocation-geocoder-google-geocoding-api-state[data-source-identifier="' + $(this).data('source-identifier') + '"]').val(0);
      Drupal.geolocation.geocoder.clearCallback($(this).data('source-identifier'));
    });
  };

  /**
   * Attach geocoder input for Google Geocoding API
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches views geocoder input for Google Geocoding API to relevant elements.
   */
  Drupal.behaviors.geolocationGeocoderGoogleGeocodingApi = {
    attach: function (context) {
      if (typeof google === 'undefined') {
        if (typeof Drupal.geolocation.loadGoogle === 'function') {
          // First load the library from google.
          Drupal.geolocation.loadGoogle(function () {
            Drupal.geolocation.geocoder.googleGeocodingAPI.attach(context);
          });
        }
      }
      else {
        Drupal.geolocation.geocoder.googleGeocodingAPI.attach(context);
      }
    }
  };

})(jQuery, Drupal);
