/**
 * @file
 *   Javascript for the Google Places API geocoder.
 */

/**
 * @property {Object} drupalSettings.geolocation.geocoder.googlePlacesAPI.restrictions
 */

/**
 * @name PlacePrediction
 * @property {String} description - Description
 * @property {String} place_id - Place ID
 */

/**
 * @name PlaceResult
 * @augments GoogleAddress
 */

/**
 * @property {Object} GoogleMap.places.PlacesServiceStatus
 * @property {String} GoogleMap.places.PlacesServiceStatus.OK
 *
 * @property {function():Object} GoogleMap.places.AutocompleteService
 * @property {function():Object} GoogleMap.places.PlacesService
 * @property {Function} GoogleMap.places.AutocompleteService.getPlacePredictions
 * @property {function(Object, Function)} GoogleMap.places.PlacesService.getDetails
 */

(function ($, Drupal) {
  'use strict';

  /* global google */

  if (typeof Drupal.geolocation.geocoder === 'undefined') {
    return false;
  }

  Drupal.geolocation.geocoder.googlePlacesAPI = {};
  drupalSettings.geolocation.geocoder.googlePlacesAPI = drupalSettings.geolocation.geocoder.googlePlacesAPI || {};

  /**
   * @param {HTMLElement} context Context
   */
  Drupal.geolocation.geocoder.googlePlacesAPI.attach = function (context) {
    $('input.geolocation-geocoder-google-places-api', context).once().autocomplete({
      autoFocus: true,
      source: function (request, response) {
        var autocompleteResults = [];
        var componentRestrictions = {};
        if (typeof drupalSettings.geolocation.geocoder.googlePlacesAPI.restrictions !== 'undefined') {
          componentRestrictions = drupalSettings.geolocation.geocoder.googlePlacesAPI.restrictions;
        }

        Drupal.geolocation.geocoder.googlePlacesAPI.autocompleteService.getPlacePredictions(
          {
            input: request.term,
            componentRestrictions: componentRestrictions
          },

          /**
           * Google Places API geocode.
           *
           * @param {PlacePrediction[]} results - Returned results
           * @param {string} status - Whether geocoding was successful
           */
          function (results, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
              $.each(results, function (index, result) {
                autocompleteResults.push({
                  value: result.description,
                  place_id: result.place_id
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
        Drupal.geolocation.geocoder.googlePlacesAPI.service.getDetails(
          {
            placeId: ui.item.place_id
          },

          /**
           * @param {PlaceResult} place GoogleAddress compatible place.
           * @param {String} status GoogleGeocoderStatus
           */
          function (place, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
              if (typeof place.geometry.location === 'undefined') {
                return;
              }
              Drupal.geolocation.geocoder.resultCallback(place, $(event.target).data('source-identifier'));
              $('.geolocation-geocoder-google-places-api-state[data-source-identifier="' + $(event.target).data('source-identifier') + '"]').val(1);
            }
          }
        );
      }
    })
    .on('input', function () {
      $('.geolocation-geocoder-google-places-api-state[data-source-identifier="' + $(this).data('source-identifier') + '"]').val(0);
      Drupal.geolocation.geocoder.clearCallback($(this).data('source-identifier'));
    });
  };

  /**
   * Attach geocoder input for Google places API
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches views geocoder input for Google places API to relevant elements.
   */
  Drupal.behaviors.geolocationGeocoderGooglePlacesApi = {
    attach: function (context) {

      if (typeof Drupal.geolocation.geocoder.googlePlacesAPI.autocompleteService === 'undefined') {
        if (typeof Drupal.geolocation.loadGoogle === 'function') {
          // First load the library from google.
          Drupal.geolocation.loadGoogle(function () {
            if (typeof Drupal.geolocation.geocoder.googlePlacesAPI.service === 'undefined') {
              var attribution_block = $('#geolocation-google-places-api-attribution');
              if (attribution_block.length === 1) {
                Drupal.geolocation.geocoder.googlePlacesAPI.service = new google.maps.places.PlacesService(attribution_block.get(0));
                Drupal.geolocation.geocoder.googlePlacesAPI.autocompleteService = new google.maps.places.AutocompleteService();

                Drupal.geolocation.geocoder.googlePlacesAPI.attach(context);
              }
            }
          });
        }
      }
      else {
        Drupal.geolocation.geocoder.googlePlacesAPI.attach(context);
      }
    }
  };

})(jQuery, Drupal);
