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
 * @property {CommonMapUpdateSettings} dynamic_map
 * @property {GoogleMapSettings} settings.google_map_settings
 * @property {String} client_location.enable
 * @property {String} client_location.update_map
 * @property {Boolean} showRawLocations
 * @property {Boolean} markerScrollToResult
 * @property {String} markerClusterer.enable
 * @property {String} markerClusterer.imagePath
 */

/**
 * @property {CommonMapSettings[]} drupalSettings.geolocation.commonMap
 */

(function ($, window, Drupal, drupalSettings) {
  'use strict';

  /* global google */

  var bubble; // Keep track if a bubble is currently open.
  var currentMarkers = []; // Keep track of all currently attached markers.
  var skipMapUpdate = false; // Setting to true will skip the next triggered map related viewsRefresh.

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
    attach: function (context, settings) {
      if (typeof Drupal.geolocation.loadGoogle === 'function') {
        // First load the library from google.
        Drupal.geolocation.loadGoogle(function () {
          initialize(settings.geolocation, context);
        });
      }
    }
  };

  function initialize(settings, context) {
    // Their could be several maps/views present. Go over each entry.
    $.each(settings.commonMap, function (mapId, mapSettings) {

      /**
       * @param {String} mapId
       * @param {CommonMapSettings} mapSettings
       */

      if (
        typeof mapSettings.dynamic_map !== 'undefined'
        && mapSettings.dynamic_map.enable
        && mapSettings.dynamic_map.hide_form
        && typeof mapSettings.dynamic_map.parameter_identifier !== 'undefined'
      ) {
        var exposedForm = $('form#views-exposed-form-' + mapSettings.dynamic_map.update_view_id.replace(/_/g, '-') + '-' + mapSettings.dynamic_map.update_view_display_id.replace(/_/g, '-'));

        if (exposedForm.length === 1) {
          exposedForm.find('input[name^="' + mapSettings.dynamic_map.parameter_identifier + '"]').each(function (index, item) {
            $(item).parent().hide();
          });

          // Hide entire form if it's empty now, except form-submit.
          if (exposedForm.find('input:visible:not(.form-submit)').length === 0) {
            exposedForm.hide();
          }
        }
      }

      // The DOM-node the map and everything else resides in.
      var map = $('#' + mapId, context);

      // If the map is not present, we can go to the next entry.
      if (!map.length) {
        return;
      }

      // Hide the graceful-fallback HTML list; map will propably work now.
      // Map-container is not hidden by default in case of graceful-fallback.
      if (typeof mapSettings.showRawLocations === 'undefined') {
        map.children('.geolocation-common-map-locations').hide();
      }
      else if (!mapSettings.showRawLocations) {
        map.children('.geolocation-common-map-locations').hide();
      }

      /**
       * @type {GeolocationMap}
       */
      var geolocationMap = {};
      geolocationMap.settings = mapSettings.settings;

      geolocationMap.container = map.children('.geolocation-common-map-container');
      geolocationMap.container.show();

      var googleMap = null;

      if (typeof Drupal.geolocation.maps !== 'undefined') {
        $.each(Drupal.geolocation.maps, function (index, item) {
          if (typeof item.container !== 'undefined') {
            if (item.container.is(geolocationMap.container)) {
              googleMap = item.googleMap;
            }
          }
        });
      }

      // Skip initial map set.
      skipMapUpdate = true;

      if (typeof googleMap !== 'undefined' && googleMap !== null) {
        skipMapUpdate = false;
        if (map.data('centre-lat') && map.data('centre-lng')) {
          var newCenter = new google.maps.LatLng({
            lat: map.data('centre-lat'),
            lng: map.data('centre-lng')
          });

          if (!googleMap.getCenter().equals(newCenter)) {
            googleMap.setCenter(newCenter);
          }
        }
        else if (
          map.data('centre-lat-north-east')
          && map.data('centre-lng-north-east')
          && map.data('centre-lat-south-west')
          && map.data('centre-lng-south-west')
        ) {
          var newBounds = {
            north: map.data('centre-lat-north-east'),
            east: map.data('centre-lng-north-east'),
            south: map.data('centre-lat-south-west'),
            west: map.data('centre-lng-south-west')
          };

          if (!googleMap.getBounds().equals(newBounds)) {
            googleMap.fitBounds(newBounds);
          }
        }
      }
      else if (map.data('centre-lat') && map.data('centre-lng')) {
        geolocationMap.lat = map.data('centre-lat');
        geolocationMap.lng = map.data('centre-lng');

        googleMap = Drupal.geolocation.addMap(geolocationMap);
      }
      else if (
        map.data('centre-lat-north-east')
        && map.data('centre-lng-north-east')
        && map.data('centre-lat-south-west')
        && map.data('centre-lng-south-west')
      ) {
        var centerBounds = {
          north: map.data('centre-lat-north-east'),
          east: map.data('centre-lng-north-east'),
          south: map.data('centre-lat-south-west'),
          west: map.data('centre-lng-south-west')
        };

        geolocationMap.lat = geolocationMap.lng = 0;
        googleMap = Drupal.geolocation.addMap(geolocationMap);

        googleMap.fitBounds(centerBounds);
      }
      else {
        geolocationMap.lat = geolocationMap.lng = 0;

        googleMap = Drupal.geolocation.addMap(geolocationMap);
      }

      /**
       * Dynamic map handling aka "AirBnB mode".
       */
      if (
        typeof mapSettings.dynamic_map !== 'undefined'
        && mapSettings.dynamic_map.enable
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
        if (typeof googleMap.updateDrupalView === 'undefined') {
          googleMap.updateDrupalView = function (dynamic_map_settings) {

            // Make sure to load current form DOM element, which will change after every AJAX operation.
            var exposedForm = $('form#views-exposed-form-' + dynamic_map_settings.update_view_id.replace(/_/g, '-') + '-' + dynamic_map_settings.update_view_display_id.replace(/_/g, '-'));

            var currentBounds = googleMap.getBounds();
            var update_path = '';
            if (skipMapUpdate === true) {
              skipMapUpdate = false;
              return;
            }

            if (
              typeof dynamic_map_settings.boundary_filter !== 'undefined'
            ) {
              if (exposedForm.length) {
                exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lat_north_east]"]').val(currentBounds.getNorthEast().lat());
                exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lng_north_east]"]').val(currentBounds.getNorthEast().lng());
                exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lat_south_west]"]').val(currentBounds.getSouthWest().lat());
                exposedForm.find('input[name="' + dynamic_map_settings.parameter_identifier + '[lng_south_west]"]').val(currentBounds.getSouthWest().lng());

                $('input[type=submit], input[type=image]', exposedForm).not('[data-drupal-selector=edit-reset]').trigger('click');
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

        if (map.data('geolocationAjaxProcessed') !== 1) {
          var geolocationMapIdleTimer;
          googleMap.addListener('idle', function () {
            clearTimeout(geolocationMapIdleTimer);
            geolocationMapIdleTimer = setTimeout(function () {
              googleMap.updateDrupalView(mapSettings.dynamic_map);
            }, mapSettings.dynamic_map.views_refresh_delay);
          });
        }
      }

      if (typeof map.data('clientlocation') !== 'undefined') {
        // Only act when location still unknown.
        if (typeof map.data('centre-lat') === 'undefined' || typeof map.data('centre-lng') === 'undefined') {
          if (
            map.data('geolocationAjaxProcessed') !== 1
            && navigator.geolocation
            && typeof mapSettings.client_location !== 'undefined'
            && mapSettings.client_location.enable === true
          ) {
            navigator.geolocation.getCurrentPosition(function (position) {
              map.data('centre-lat', position.coords.latitude);
              map.data('centre-lng', position.coords.longitude);

              var newLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

              googleMap.setCenter(newLocation);
              googleMap.setZoom(parseInt(mapSettings.settings.google_map_settings.zoom));

              Drupal.geolocation.drawAccuracyIndicator(newLocation, position.coords.accuracy, googleMap);

              if (
                typeof mapSettings.client_location.update_map !== 'undefined'
                && mapSettings.client_location.update_map === true
                && typeof mapSettings.dynamic_map !== 'undefined'
              ) {
                googleMap.updateDrupalView(mapSettings.dynamic_map);
              }
            });
          }
        }
      }
      $.each(currentMarkers, function (markerIndex, marker) {
        marker.setMap(null);
      });

      // A google maps API tool to re-center the map on its content.
      var bounds = new google.maps.LatLngBounds();

      // Add the locations to the map.
      map.find('.geolocation-common-map-locations .geolocation').each(function (key, location) {
        location = $(location);
        var position = new google.maps.LatLng(location.data('lat'), location.data('lng'));

        bounds.extend(position);

        var marker = new google.maps.Marker({
          position: position,
          map: googleMap,
          title: location.children('h2').text(),
          content: location.html()
        });

        if (typeof location.data('icon') !== 'undefined') {
          marker.setIcon(location.data('icon'));
        }

        currentMarkers.push(marker);

        marker.addListener('click', function () {
          if (mapSettings.markerScrollToResult === true) {
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
          else {
            if (bubble) {
              bubble.close();
            }
            bubble = new google.maps.InfoWindow({
              content: marker.content,
              maxWidth: 200,
              disableAutoPan: mapSettings.settings.google_map_settings.disableAutoPan
            });
            bubble.open(googleMap, marker);
          }
        });
      });

      /**
       * MarkerClusterer handling.
       */
      if (
        typeof mapSettings.markerClusterer !== 'undefined'
        && mapSettings.markerClusterer.enable
      ) {

        /* global MarkerClusterer */

        var imagePath = '';
        if (mapSettings.markerClusterer.imagePath) {
          imagePath = mapSettings.markerClusterer.imagePath;
        }
        else {
          imagePath = 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m';
        }

        new MarkerClusterer(
          googleMap,
          currentMarkers, {
            imagePath: imagePath
          }
        );
      }

      if (map.data('fitbounds') === 1) {
        // Fit map center and zoom to all currently loaded markers.
        googleMap.fitBounds(bounds);
      }
    });
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
