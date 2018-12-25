/**
 * @file
 * Javascript for the Google map form element.
 */

/**
 * @name GeolocationGoogleMapFormElementSettings
 * @property {String} maxLocations
 */

/**
 * @property {GeolocationGoogleMapFormElementSettings[]} drupalSettings.geolocation.googleMapFormElements
 */

/**
 * @name GoogleMarker
 * @property {Number} inputIndex
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @namespace
   */
  Drupal.geolocation = Drupal.geolocation || {};

  /**
   * Attach Google Maps form element functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Google Maps form element functionality to relevant elements.
   */
  Drupal.behaviors.geolocationGoogleMapFormElements = {
    attach: function (context, drupalSettings) {
      $.each(
        drupalSettings.geolocation.googleMapFormElements,

        /**
         * @param {String} mapId - canvasId of target map
         * @param {GeolocationGoogleMapFormElementSettings} formElementSettings - settings for form element
         */
        function (mapId, formElementSettings) {
          /** @type {jQuery} */
          var container = $('#geolocation-google-map-form-element-' + mapId);

          if (container.length !== 1) {
            return;
          }

          Drupal.geolocation.addMapLoadedCallback(function (map) {
            // Remove all markers not represented by input.
            map.mapMarkers.forEach(function (currentMarker, index) {
              if (index >= parseInt(formElementSettings.maxLocations)) {
                currentMarker.setMap(null);
                map.mapMarkers.splice(index, 1);
              }
              else {
                currentMarker.setLabel('X');
                if (typeof currentMarker.inputIndex === 'undefined') {
                  currentMarker.inputIndex = index;
                }
                currentMarker.addListener('click', function (event) {
                  currentMarker.setMap(null);
                  container.find('.geolocation-map-input-' + index + ' input[type=text]').val('');
                });
              }
            });

            google.maps.event.addListener(map.googleMap, 'click', function (event) {
              var availableInput = container.find('.geolocation-map-input').filter(function() {
                return (
                  $(this).find('input.geolocation-map-input-latitude').val() === ''
                  && $(this).find('input.geolocation-map-input-longitude').val() === ''
                );
              }).first();

              if (availableInput.length) {
                var newMarker = Drupal.geolocation.setMapMarker(map, {
                  position: event.latLng,
                  label: 'X',
                  map: map.googleMap,
                  title: event.latLng.lat() + ', ' + event.latLng.lng()
                });

                availableInput.find('input.geolocation-map-input-latitude').val(event.latLng.lat());
                availableInput.find('input.geolocation-map-input-longitude').val(event.latLng.lng());

                newMarker.addListener('click', function (event) {
                  newMarker.setMap(null);
                  availableInput.find('input.geolocation-map-input-latitude').val('');
                  availableInput.find('input.geolocation-map-input-longitude').val('');
                });
              }
              else {
                alert(Drupal.t("All available location inputs are filled. You can clear existing locations by clicking them."));
              }
            });
          }, mapId);
        }
      );
    }
  };

})(jQuery, Drupal);
