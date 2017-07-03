/**
 * @file
 *   Javascript for the geolocation module.
 */

/**
 * @param {Object} drupalSettings.geolocation
 * @param {String} drupalSettings.geolocation.google_map_url
 */

/**
 * @name GoogleMapSettings
 * @property {String} info_auto_display
 * @property {String} height
 * @property {String} width
 * @property {String} zoom
 * @property {String} type
 * @property {Boolean} scrollwheel
 * @property {Boolean} preferScrollingToZooming
 * @property {String} gestureHandling
 * @property {Boolean} panControl
 * @property {Boolean} mapTypeControl
 * @property {Boolean} scaleControl
 * @property {Boolean} streetViewControl
 * @property {Boolean} overviewMapControl
 * @property {Boolean} zoomControl
 * @property {Object} zoomControlOptions
 * @property {String} mapTypeId
 * @property {String} info_text
 */

/**
 * @typedef {Object} GoogleMapBounds
 * @property {function():GoogleMapLatLng} getNorthEast
 * @property {function():GoogleMapLatLng} getSouthWest
 */

/**
 * @typedef {Object} GoogleMapLatLng
 * @property {function():float} lat
 * @property {function():float} lng
 */

/**
 * @typedef {Object} GoogleMapEvent
 * @property {Function} addDomListener
 */

/**
 * @typedef {Object} AddressComponent
 * @property {String} long_name - Long component name
 * @property {String} short_name - Short component name
 * @property {String[]} types - Component type
 * @property {GoogleGeometry} geometry
 */

/**
 * @typedef {Object} GoogleAddress
 * @property {AddressComponent[]} address_components - Components
 * @property {String} formatted_address - Formatted address
 * @property {GoogleGeometry} geometry - Geometry
 */

/**
 * @typedef {Object} GoogleGeometry
 * @property {GoogleMapLatLng} location - Location
 * @property {String} location_type - Location type
 * @property {GoogleMapBounds} viewport - Viewport
 * @property {GoogleMapBounds} bounds - Bounds (optionally)
 */

/**
 * @typedef {Object} GoogleMap
 * @property {Object} ZoomControlStyle
 * @property {String} ZoomControlStyle.LARGE
 *
 * @property {Object} ControlPosition
 * @property {String} ControlPosition.LEFT_TOP
 * @property {String} ControlPosition.TOP_LEFT
 * @property {String} ControlPosition.RIGHT_BOTTOM
 * @property {String} ControlPosition.RIGHT_CENTER
 *
 * @property {Object} MapTypeId
 * @property {String} MapTypeId.ROADMAP
 *
 * @property {Function} LatLng
 * @property {Function} LatLngBounds
 *
 * @function
 * @property Map
 *
 * @function
 * @property InfoWindow
 *
 * @function
 * @property {function(GoogleMapLatLng, GoogleMap, string, string):Object} Marker
 * @property {Function} Marker.setPosition
 * @property {Function} Marker.setMap
 * @property {Function} Marker.setIcon
 * @property {Function} Marker.setTitle
 *
 * @property {function(Object):Object} Circle
 * @property {function():GoogleMapBounds} Circle.getBounds()
 *
 * @property {Function} fitBounds
 *
 * @property {Function} setCenter
 * @property {Function} setZoom
 * @property {Function} getZoom
 * @property {Function} setOptions
 *
 * @property {function():GoogleMapBounds} getBounds
 * @property {function():GoogleMapLatLng} getCenter
 */

/**
 * @typedef {Object} google
 * @property {GoogleMap} maps
 * @property {GoogleMapEvent[]} events
 */

/**
 * @typedef {Object} GeolocationMap
 * @property {GoogleMapSettings} settings
 * @property {GoogleMap} googleMap
 * @property {Number} lat
 * @property {Number} lng
 * @property {Object} container
 * @property {Object} marker
 * @property {Object} infowindow
 */

(function ($, _, Drupal, drupalSettings) {
  'use strict';

  /* global google */

  /**
   * JSLint handing.
   *
   *  @callback geolocationCallback
   */

  /**
   * @namespace
   */
  Drupal.geolocation = Drupal.geolocation || {};

  // Google Maps are loaded lazily. In some situations load_google() is called twice, which results in
  // "You have included the Google Maps API multiple times on this page. This may cause unexpected errors." errors.
  // This flag will prevent repeat $.getScript() calls.
  Drupal.geolocation.maps_api_loading = false;

  /**
   * Gets the default settings for the google map.
   *
   * @return {{GoogleMapSettings}}.
   */
  Drupal.geolocation.defaultSettings = function () {
    return {
      google_map_settings: {
        scrollwheel: false,
        panControl: false,
        mapTypeControl: true,
        scaleControl: false,
        streetViewControl: false,
        overviewMapControl: false,
        zoomControl: true,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        zoom: 2,
        style: [],
        gestureHandling: 'auto'
      }
    };
  };

  /**
   * Provides the callback that is called when maps loads.
   */
  Drupal.geolocation.googleCallback = function () {
    // Ensure callbacks array;
    Drupal.geolocation.googleCallbacks = Drupal.geolocation.googleCallbacks || [];

    // Wait until the window load event to try to use the maps library.
    $(document).ready(function (e) {
      _.invoke(Drupal.geolocation.googleCallbacks, 'callback');
      Drupal.geolocation.googleCallbacks = [];
    });
  };

  /**
   * Adds a callback that will be called once the maps library is loaded.
   *
   * @param {geolocationCallback} callback - The callback
   */
  Drupal.geolocation.addCallback = function (callback) {
    Drupal.geolocation.googleCallbacks = Drupal.geolocation.googleCallbacks || [];
    Drupal.geolocation.googleCallbacks.push({callback: callback});
  };

  /**
   * Load google maps and set a callback to run when it's ready.
   *
   * @param {geolocationCallback} callback - The callback
   */
  Drupal.geolocation.loadGoogle = function (callback) {
    // Add the callback.
    Drupal.geolocation.addCallback(callback);

    // Check for google maps.
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
      if (Drupal.geolocation.maps_api_loading === true) {
        return;
      }

      Drupal.geolocation.maps_api_loading = true;
      // Google maps isn't loaded so lazy load google maps.

      if (typeof drupalSettings.geolocation.google_map_url !== 'undefined') {
        $.getScript(drupalSettings.geolocation.google_map_url)
          .done(function () {
            Drupal.geolocation.maps_api_loading = false;
          });
      }
      else {
        console.error('Geolocation - Google map url not set.');
      }
    }
    else {
      // Google maps loaded. Run callback.
      Drupal.geolocation.googleCallback();
    }
  };

  /**
   * Load google maps and set a callback to run when it's ready.
   *
   * @param {GeolocationMap} map - Container of settings and ID.
   *
   * @return {object} - The google map object.
   */
  Drupal.geolocation.addMap = function (map) {
    // Set the container size.
    map.container.css({
      height: map.settings.google_map_settings.height,
      width: map.settings.google_map_settings.width
    });

    // Get the center point.
    var center = new google.maps.LatLng(map.lat, map.lng);

    /**
     * Create the map object and assign it to the map.
     *
     * @type {GoogleMap} map.googleMap
     */
    map.googleMap = new google.maps.Map(map.container.get(0), {
      zoom: parseInt(map.settings.google_map_settings.zoom),
      center: center,
      mapTypeId: google.maps.MapTypeId[map.settings.google_map_settings.type],
      mapTypeControlOptions: {
        position: google.maps.ControlPosition.RIGHT_BOTTOM
      },
      zoomControl: map.settings.google_map_settings.zoomControl,
      zoomControlOptions: {
        style: google.maps.ZoomControlStyle.LARGE,
        position: google.maps.ControlPosition.RIGHT_CENTER
      },
      streetViewControl: map.settings.google_map_settings.streetViewControl,
      streetViewControlOptions: {
        position: google.maps.ControlPosition.RIGHT_CENTER
      },
      mapTypeControl: map.settings.google_map_settings.mapTypeControl,
      scrollwheel: map.settings.google_map_settings.scrollwheel,
      disableDoubleClickZoom: map.settings.google_map_settings.disableDoubleClickZoom,
      draggable: map.settings.google_map_settings.draggable,
      styles: map.settings.google_map_settings.style,
      gestureHandling: map.settings.google_map_settings.gestureHandling
    });

    if (!Drupal.geolocation.hasOwnProperty('maps')) {
      Drupal.geolocation.maps = [];
    }

    if (map.settings.google_map_settings.scrollwheel && map.settings.google_map_settings.preferScrollingToZooming) {
      map.googleMap.setOptions({scrollwheel: false});
      map.googleMap.addListener('click', function () {
        map.googleMap.setOptions({scrollwheel: true});
      });
    }

    Drupal.geolocation.maps.push(map);

    return map.googleMap;
  };

  /**
   * Set/Update a marker on a map
   *
   * @param {GoogleMapLatLng} latLng - A location (latLng) object from google maps API.
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   * @param {string} title - Title for marker.
   * @param {string} infoText - Content of info window.
   */
  Drupal.geolocation.setMapMarker = function (latLng, map, title, infoText) {
    // make sure the marker exists.
    if (map.marker instanceof google.maps.Marker) {
      map.marker.setPosition(latLng);
      map.marker.setMap(map.googleMap);
      if (title.length > 0) {
        map.marker.setTitle(title);
      }

      if (map.infowindow && infoText.length > 0) {
        map.infowindow.setContent(infoText);
      }
    }
    else {

      // Add the marker to the map.
      map.marker = new google.maps.Marker({
        position: latLng,
        map: map.googleMap,
        title: title
      });

      // Add the info window event if the info text has been set.
      if (infoText.length > 0) {

        // Set the info popup text.
        map.infowindow = new google.maps.InfoWindow({
          content: infoText,
          disableAutoPan: map.settings.disableAutoPan
        });

        map.marker.addListener('click', function () {
          map.infowindow.open(map.googleMap, map.marker);
        });

        if (map.settings.google_map_settings.info_auto_display) {
          map.infowindow.open(map.googleMap, map.marker);
        }
      }
    }
  };

  /**
   * Draw a circle indicating accuracy and slowly fade it out.
   *
   * @param {GoogleMapLatLng} location - A location (latLng) object from google maps API.
   * @param {int} accuracy - Accuracy in meter.
   * @param {GoogleMap} map - Map to draw on.
   */
  Drupal.geolocation.drawAccuracyIndicator = function (location, accuracy, map) {

    // Draw a circle representing the accuracy radius of HTML5 geolocation.
    var circle = new google.maps.Circle({
      center: location,
      radius: accuracy,
      map: map,
      fillColor: '#4285F4',
      fillOpacity: 0.5,
      strokeColor: '#4285F4',
      strokeOpacity: 1,
      clickable: false
    });

    // Set the zoom level to the accuracy circle's size.
    map.fitBounds(circle.getBounds());

    // Fade circle away.
    setInterval(fadeCityCircles, 100);

    function fadeCityCircles() {
      var fillOpacity = circle.get('fillOpacity');
      fillOpacity -= 0.02;

      var strokeOpacity = circle.get('strokeOpacity');
      strokeOpacity -= 0.04;

      if (
        strokeOpacity > 0
        && fillOpacity > 0
      ) {
        circle.setOptions({fillOpacity: fillOpacity, strokeOpacity: strokeOpacity});
      }
      else {
        circle.setMap(null);
      }
    }
  };

})(jQuery, _, Drupal, drupalSettings);
