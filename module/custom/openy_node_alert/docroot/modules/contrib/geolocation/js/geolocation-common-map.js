/**
 * @file
 * Handle the common map.
 */

/**
 * @name CommonMapUpdateSettings
 * @property {String} enable
 * @property {String} hide_form
 * @property {number} views_refresh_delay
 * @property {String} update_view_id
 * @property {String} update_view_display_id
 * @property {String} boundary_filter
 * @property {String} parameter_identifier
 */

/**
 * @name CommonMapSettings
 * @property {Object} settings
 * @property {GoogleMapSettings} settings.google_map_settings
 * @property {CommonMapUpdateSettings} dynamic_map
 * @property {String} client_location.enable
 * @property {String} client_location.update_map
 * @property {Boolean} showRawLocations
 * @property {Boolean} markerScrollToResult
 * @property {String} markerClusterer.enable
 * @property {String} markerClusterer.imagePath
 * @property {Object} markerClusterer.styles
 * @property {String} contextPopupContent.enable
 * @property {String} contextPopupContent.content
 */

/**
 * @property {CommonMapSettings[]} drupalSettings.geolocation.commonMap
 */

/**
 * @property {function(CommonMapUpdateSettings)} GeolocationMap.updateDrupalView
 * @property {Object} GeolocationMap.markerClusterer
 */

(function ($, window, Drupal, drupalSettings) {
  'use strict';

  /* global google */

  var skipMapIdleEventHandler = false; // Setting to true will skip the next triggered map related viewsRefresh.

  /**
   * @namespace
   */
  Drupal.geolocation = Drupal.geolocation || {};

  /**
   * Attach common map style functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches common map style functionality to relevant elements.
   */
  Drupal.behaviors.geolocationCommonMap = {
    attach: function (context, drupalSettings) {
      if (typeof Drupal.geolocation.loadGoogle === 'function') {
        // First load the library from google.
        Drupal.geolocation.loadGoogle(function () {
          initialize(drupalSettings.geolocation, context);
        });
      }
    }
  };

  function initialize(settings, context) {

    $.each(
      settings.commonMap,

      /**
       * @param {String} mapId - canvasId of current map
       * @param {CommonMapSettings} commonMapSettings - settings for current map
       */
      function (mapId, commonMapSettings) {

        /*
         * Hide form if requested.
         */
        if (
          typeof commonMapSettings.dynamic_map !== 'undefined'
          && commonMapSettings.dynamic_map.enable
          && commonMapSettings.dynamic_map.hide_form
          && typeof commonMapSettings.dynamic_map.parameter_identifier !== 'undefined'
        ) {
          var exposedForm = $('form#views-exposed-form-' + commonMapSettings.dynamic_map.update_view_id.replace(/_/g, '-') + '-' + commonMapSettings.dynamic_map.update_view_display_id.replace(/_/g, '-'));

          if (exposedForm.length === 1) {
            exposedForm.find('input[name^="' + commonMapSettings.dynamic_map.parameter_identifier + '"]').each(function (index, item) {
              $(item).parent().hide();
            });

            // Hide entire form if it's empty now, except form-submit.
            if (exposedForm.find('input:visible:not(.form-submit), select:visible').length === 0) {
              exposedForm.hide();
            }
          }
        }

        // The DOM-node the map and everything else resides in.
        /** @type {jQuery} */
        var mapWrapper = $('#' + mapId, context);

        // If the map is not present, we can go to the next entry.
        if (!mapWrapper.length) {
          return;
        }

        // Hide the graceful-fallback HTML list; map will propably work now.
        // Map-container is not hidden by default in case of graceful-fallback.
        if (typeof commonMapSettings.showRawLocations === 'undefined') {
          mapWrapper.find('.geolocation-common-map-locations').hide();
        }
        else if (!commonMapSettings.showRawLocations) {
          mapWrapper.find('.geolocation-common-map-locations').hide();
        }

        /**
         * @type {GeolocationMap}
         */
        var geolocationMap = {};

        geolocationMap.id = mapId;

        /*
         * Check for map already created (i.e. after AJAX)
         */
        if (typeof Drupal.geolocation.maps !== 'undefined') {
          $.each(Drupal.geolocation.maps, function (index, map) {
            if (typeof map.container !== 'undefined') {
              if (map.container.is(mapWrapper.find('.geolocation-common-map-container'))) {
                geolocationMap = map;
              }
            }
          });
        }

        /*
         * Update existing map, depending on present data-attribute settings.
         */
        if (typeof geolocationMap.googleMap !== 'undefined') {
          if (mapWrapper.data('centre-lat') && mapWrapper.data('centre-lng')) {
            var newCenter = new google.maps.LatLng(
              mapWrapper.data('centre-lat'),
              mapWrapper.data('centre-lng')
            );

            if (!geolocationMap.googleMap.getCenter().equals(newCenter)) {
              skipMapIdleEventHandler = true;
              geolocationMap.googleMap.setCenter(newCenter);
            }
          }
          else if (
            mapWrapper.data('centre-lat-north-east')
            && mapWrapper.data('centre-lng-north-east')
            && mapWrapper.data('centre-lat-south-west')
            && mapWrapper.data('centre-lng-south-west')
          ) {
            var newBounds = {
              north: mapWrapper.data('centre-lat-north-east'),
              east: mapWrapper.data('centre-lng-north-east'),
              south: mapWrapper.data('centre-lat-south-west'),
              west: mapWrapper.data('centre-lng-south-west')
            };

            if (!geolocationMap.googleMap.getBounds().equals(newBounds)) {
              skipMapIdleEventHandler = true;
              geolocationMap.googleMap.fitBounds(newBounds);
            }
          }
        }

        /*
         * Instantiate new map.
         */
        else {
          geolocationMap.settings = {};
          geolocationMap.settings.google_map_settings = commonMapSettings.settings.google_map_settings;

          geolocationMap.container = mapWrapper.find('.geolocation-common-map-container').first();
          geolocationMap.container.show();

          if (
            mapWrapper.data('centre-lat')
            && mapWrapper.data('centre-lng')
          ) {
            geolocationMap.lat = mapWrapper.data('centre-lat');
            geolocationMap.lng = mapWrapper.data('centre-lng');

            skipMapIdleEventHandler = true;
            geolocationMap.googleMap = Drupal.geolocation.addMap(geolocationMap);
          }
          else if (
            mapWrapper.data('centre-lat-north-east')
            && mapWrapper.data('centre-lng-north-east')
            && mapWrapper.data('centre-lat-south-west')
            && mapWrapper.data('centre-lng-south-west')
          ) {
            var centerBounds = {
              north: mapWrapper.data('centre-lat-north-east'),
              east: mapWrapper.data('centre-lng-north-east'),
              south: mapWrapper.data('centre-lat-south-west'),
              west: mapWrapper.data('centre-lng-south-west')
            };

            geolocationMap.lat = geolocationMap.lng = 0;
            skipMapIdleEventHandler = true;
            geolocationMap.googleMap = Drupal.geolocation.addMap(geolocationMap);

            skipMapIdleEventHandler = true;
            geolocationMap.googleMap.fitBounds(centerBounds);
          }
          else {
            geolocationMap.lat = geolocationMap.lng = 0;

            skipMapIdleEventHandler = true;
            geolocationMap.googleMap = Drupal.geolocation.addMap(geolocationMap);
          }
        }

        /**
         * Dynamic map handling aka "AirBnB mode".
         */
        if (
          typeof commonMapSettings.dynamic_map !== 'undefined'
          && commonMapSettings.dynamic_map.enable
        ) {

          /**
           * Update the view depending on dynamic map settings and capability.
           *
           * One of several states might occur now. Possible state depends on whether:
           * - view using AJAX is enabled
           * - map view is the containing (page) view or an attachment
           * - the exposed form is present and contains the boundary filter
           * - map settings are consistent
           *
           * Given these factors, map boundary changes can be handled in one of three ways:
           * - trigger the views AJAX "RefreshView" command
           * - trigger the exposed form causing a regular POST reload
           * - fully reload the website
           *
           * These possibilities are ordered by UX preference.
           *
           * @param {CommonMapUpdateSettings} dynamic_map_settings
           *   The dynamic map settings to update the map.
           */
          if (typeof geolocationMap.updateDrupalView === 'undefined') {
            geolocationMap.updateDrupalView = function (dynamic_map_settings) {
              // Make sure to load current form DOM element, which will change after every AJAX operation.
              var exposedForm = $('form#views-exposed-form-' + dynamic_map_settings.update_view_id.replace(/_/g, '-') + '-' + dynamic_map_settings.update_view_display_id.replace(/_/g, '-'));

              var currentBounds = geolocationMap.googleMap.getBounds();
              var update_path = '';

              if (
                typeof dynamic_map_settings.boundary_filter !== 'undefined'
              ) {
                if (exposedForm.length) {
                  exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lat_north_east]"]').val(currentBounds.getNorthEast().lat());
                  exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lng_north_east]"]').val(currentBounds.getNorthEast().lng());
                  exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lat_south_west]"]').val(currentBounds.getSouthWest().lat());
                  exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lng_south_west]"]').val(currentBounds.getSouthWest().lng());

                  $('input[type=submit], input[type=image], button[type=submit]', exposedForm).not('[data-drupal-selector=edit-reset]').trigger('click');
                }
                // No AJAX, no form, just enforce a page reload with GET parameters set.
                else {
                  if (window.location.search.length) {
                    update_path = window.location.search + '&';
                  }
                  else {
                    update_path = '?';
                  }
                  update_path += dynamic_map_settings.parameter_identifier + '[lat_north_east]=' + currentBounds.getNorthEast().lat();
                  update_path += '&' + dynamic_map_settings.parameter_identifier + '[lng_north_east]=' + currentBounds.getNorthEast().lng();
                  update_path += '&' + dynamic_map_settings.parameter_identifier + '[lat_south_west]=' + currentBounds.getSouthWest().lat();
                  update_path += '&' + dynamic_map_settings.parameter_identifier + '[lng_south_west]=' + currentBounds.getSouthWest().lng();

                  window.location = update_path;
                }
              }
            };
          }

          if (mapWrapper.data('geolocationAjaxProcessed') !== 1) {
            var geolocationMapIdleTimer;
            geolocationMap.googleMap.addListener('idle', function () {
              if (skipMapIdleEventHandler === true) {
                skipMapIdleEventHandler = false;
                return;
              }
              clearTimeout(geolocationMapIdleTimer);
              geolocationMapIdleTimer = setTimeout(function () {
                geolocationMap.updateDrupalView(commonMapSettings.dynamic_map);
              }, commonMapSettings.dynamic_map.views_refresh_delay);
            });
          }
        }

        /**
         * Client location handling.
         */
        if (typeof mapWrapper.data('clientlocation') !== 'undefined' && !mapWrapper.hasClass('clientlocation-processed')) {
          mapWrapper.addClass('clientlocation-processed');
          if (
            mapWrapper.data('geolocationAjaxProcessed') !== 1
            && navigator.geolocation
            && typeof commonMapSettings.client_location !== 'undefined'
            && commonMapSettings.client_location.enable === true
          ) {
            navigator.geolocation.getCurrentPosition(function (position) {
              mapWrapper.data('centre-lat', position.coords.latitude);
              mapWrapper.data('centre-lng', position.coords.longitude);

              var newLocation = new google.maps.LatLng(parseFloat(position.coords.latitude), parseFloat(position.coords.longitude));

              skipMapIdleEventHandler = true;
              geolocationMap.googleMap.setCenter(newLocation);
              if (skipMapIdleEventHandler !== true) {
                skipMapIdleEventHandler = true;
              }

              geolocationMap.googleMap.setZoom(geolocationMap.settings.google_map_settings.zoom);

              Drupal.geolocation.drawAccuracyIndicator(newLocation, parseInt(position.coords.accuracy), geolocationMap.googleMap);

              if (
                typeof commonMapSettings.client_location.update_map !== 'undefined'
                && commonMapSettings.client_location.update_map === true
                && typeof commonMapSettings.dynamic_map !== 'undefined'
              ) {
                skipMapIdleEventHandler = true;
                geolocationMap.updateDrupalView(commonMapSettings.dynamic_map);
              }
            });
          }
        }

        /**
         * Result handling.
         */
        // A Google Maps API tool to re-center the map on its content.
        var bounds = new google.maps.LatLngBounds();
        Drupal.geolocation.removeMapMarker(geolocationMap);

        /*
         * Add the locations to the map.
         */
        mapWrapper.find('.geolocation-common-map-locations .geolocation').each(function (key, location) {

          /** @type {jQuery} */
          location = $(location);
          var position = new google.maps.LatLng(parseFloat(location.data('lat')), parseFloat(location.data('lng')));

          bounds.extend(position);

          /**
           * @type {GoogleMarkerSettings}
           */
          var markerConfig = {
            position: position,
            map: geolocationMap.googleMap,
            title: location.children('.location-title').html(),
            infoWindowContent: location.html(),
            infoWindowSolitary: true
          };

          if (typeof location.data('icon') !== 'undefined') {
            markerConfig.icon = location.data('icon');
          }

          if (typeof location.data('markerLabel') !== 'undefined') {
            markerConfig.label = location.data('markerLabel').toString();
          }

          var skipInfoWindow = false;
          if (commonMapSettings.markerScrollToResult === true) {
            skipInfoWindow = true;
          }

          var marker = Drupal.geolocation.setMapMarker(geolocationMap, markerConfig, skipInfoWindow);

          marker.addListener('click', function () {
            if (commonMapSettings.markerScrollToResult === true) {
              var target = $('[data-location-id="' + location.data('location-id') + '"]:visible').first();

              // Alternatively select by class.
              if (target.length === 0) {
                target = $('.geolocation-location-id-' + location.data('location-id') + ':visible').first();
              }

              if (target.length === 1) {
                $('html, body').animate({
                  scrollTop: target.offset().top
                }, 'slow');
              }
            }
          });
        });

        /**
         * Context popup handling.
         */
        if (
          typeof commonMapSettings.contextPopupContent !== 'undefined'
          && commonMapSettings.contextPopupContent.enable
        ) {

          /** @type {jQuery} */
          var contextContainer = jQuery('<div class="geolocation-context-popup"></div>');
          contextContainer.hide();
          contextContainer.appendTo(geolocationMap.container);

          /**
           * Gets the default settings for the Google Map.
           *
           * @param {GoogleMapLatLng} latLng - Coordinates.
           * @return {GoogleMapPoint} - Pixel offset against top left corner of map container.
           */
          geolocationMap.googleMap.fromLatLngToPixel = function (latLng) {
            var numTiles = 1 << geolocationMap.googleMap.getZoom();
            var projection = geolocationMap.googleMap.getProjection();
            var worldCoordinate = projection.fromLatLngToPoint(latLng);
            var pixelCoordinate = new google.maps.Point(
              worldCoordinate.x * numTiles,
              worldCoordinate.y * numTiles);

            var topLeft = new google.maps.LatLng(
              geolocationMap.googleMap.getBounds().getNorthEast().lat(),
              geolocationMap.googleMap.getBounds().getSouthWest().lng()
            );

            var topLeftWorldCoordinate = projection.fromLatLngToPoint(topLeft);
            var topLeftPixelCoordinate = new google.maps.Point(
              topLeftWorldCoordinate.x * numTiles,
              topLeftWorldCoordinate.y * numTiles);

            return new google.maps.Point(
              pixelCoordinate.x - topLeftPixelCoordinate.x,
              pixelCoordinate.y - topLeftPixelCoordinate.y
            );
          };

          google.maps.event.addListener(geolocationMap.googleMap, 'rightclick', function (event) {
            var content = Drupal.formatString(commonMapSettings.contextPopupContent.content, {
              '@lat': event.latLng.lat(),
              '@lng': event.latLng.lng()
            });

            contextContainer.html(content);

            if (content.length > 0) {
              var pos = geolocationMap.googleMap.fromLatLngToPixel(event.latLng);
              contextContainer.show();
              contextContainer.css('left', pos.x);
              contextContainer.css('top', pos.y);
            }
          });

          google.maps.event.addListener(geolocationMap.googleMap, 'click', function (event) {
            if (typeof contextContainer !== 'undefined') {
              contextContainer.hide();
            }
          });
        }

        /**
         * MarkerClusterer handling.
         */
        if (
          typeof commonMapSettings.markerClusterer !== 'undefined'
          && commonMapSettings.markerClusterer.enable
          && typeof MarkerClusterer !== 'undefined'
        ) {

          /* global MarkerClusterer */

          var imagePath = '';
          if (commonMapSettings.markerClusterer.imagePath) {
            imagePath = commonMapSettings.markerClusterer.imagePath;
          }
          else {
            imagePath = 'https://cdn.rawgit.com/googlemaps/js-marker-clusterer/gh-pages/images/m';
          }

          var markerClustererStyles = '';
          if (typeof commonMapSettings.markerClusterer.styles !== 'undefined') {
            markerClustererStyles = commonMapSettings.markerClusterer.styles;
          }

          geolocationMap.markerClusterer = new MarkerClusterer(
            geolocationMap.googleMap,
            geolocationMap.mapMarkers,
            {
              imagePath: imagePath,
              styles: markerClustererStyles
            }
          );
        }

        if (mapWrapper.data('fitbounds') === 1) {
          // Fit map center and zoom to all currently loaded markers.
          skipMapIdleEventHandler = true;
          geolocationMap.googleMap.fitBounds(bounds);
        }
      }
    );
  }

  /**
   * Insert updated map contents into the document.
   *
   * ATTENTION: This is a straight ripoff from misc/ajax.js ~line 1017 insert() function.
   * Please read all code commentary there first!
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   * @param {string} response.data
   *   The data to use with the jQuery method.
   * @param {string} [response.method]
   *   The jQuery DOM manipulation method to be used.
   * @param {string} [response.selector]
   *   A optional jQuery selector string.
   * @param {object} [response.settings]
   *   An optional array of settings that will be used.
   * @param {number} [status]
   *   The XMLHttpRequest status.
   */
  Drupal.AjaxCommands.prototype.geolocationCommonMapsUpdate = function (ajax, response, status) {
    // See function comment for code origin first before any changes!
    var $wrapper = response.selector ? $(response.selector) : $(ajax.wrapper);
    var settings = response.settings || ajax.settings || drupalSettings;

    var $new_content_wrapped = $('<div></div>').html(response.data);
    var $new_content = $new_content_wrapped.contents();

    if ($new_content.length !== 1 || $new_content.get(0).nodeType !== 1) {
      $new_content = $new_content.parent();
    }

    Drupal.detachBehaviors($wrapper.get(0), settings);

    // Retain existing map if possible, to avoid jumping and improve UX.
    if (
      $new_content.find('.geolocation-common-map-container').length > 0
      && $wrapper.find('.geolocation-common-map-container').length > 0
    ) {
      var detachedMap = $wrapper.find('.geolocation-common-map-container').first().detach();
      $new_content.find('.geolocation-common-map-container').first().replaceWith(detachedMap);
      $new_content.find('.geolocation-common-map').data('geolocation-ajax-processed', 1);
    }

    $wrapper.replaceWith($new_content);

    // Attach all JavaScript behaviors to the new content, if it was
    // successfully added to the page, this if statement allows
    // `#ajax['wrapper']` to be optional.
    if ($new_content.parents('html').length > 0) {
      // Apply any settings from the returned JSON if available.
      Drupal.attachBehaviors($new_content.get(0), settings);
    }
  };

})(jQuery, window, Drupal, drupalSettings);
