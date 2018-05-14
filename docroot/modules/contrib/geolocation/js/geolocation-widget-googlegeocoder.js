/**
 * @file
 *   Javascript for the Google geocoder widget.
 */

/**
 * @name GeocoderWidgetSettings
 * @property {String} addressFieldTarget
 * @property {String} addressFieldExpliciteActions
 * @property {String} autoClientLocation
 * @property {String} autoClientLocationMarker
 * @property {String} locationSet
 */

/**
 * @param {GeocoderWidgetSettings[]} drupalSettings.geolocation.widgetSettings
 * @param {GeolocationMap[]} drupalSettings.geolocation.widgetMaps
 */

/**
 * Callback for location found or set by widget.
 *
 * @callback geolocationGoogleGeocoderLocationCallback
 * @param {GoogleMapLatLng} location - Google address.
 */

/**
 * Callback for location unset by widget.
 *
 * @callback geolocationGoogleGeocoderClearCallback
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /* global google */

  /**
   * @namespace
   */
  Drupal.geolocation = Drupal.geolocation || {};
  Drupal.geolocation.geocoderWidget = Drupal.geolocation.geocoderWidget || {};

  Drupal.geolocation.geocoderWidget.locationCallbacks = Drupal.geolocation.geocoderWidget.locationCallbacks || [];
  Drupal.geolocation.geocoderWidget.clearCallbacks = Drupal.geolocation.geocoderWidget.clearCallbacks || [];

  /**
   * Attach geocoder functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches geocoder functionality to relevant elements.
   */
  Drupal.behaviors.geolocationGeocoderWidget = {
    attach: function (context, drupalSettings) {
      // Ensure itterables.
      drupalSettings.geolocation = drupalSettings.geolocation || {widgetMaps: [], widgetSettings: []};
      // Make sure the lazy loader is available.
      if (typeof Drupal.geolocation.loadGoogle === 'function') {
        // First load the library from google.
        Drupal.geolocation.loadGoogle(function () {
          // This won't fire until window load.
          initialize(drupalSettings.geolocation.widgetMaps, context);
        });
      }
    }
  };

  /**
   * Runs after the Google Maps API is available
   *
   * @param {GeolocationMap[]} maps - The Google Maps object.
   * @param {object} context - The html context.
   */
  function initialize(maps, context) {

    Drupal.geolocation.geocoderWidget.geocoder = new google.maps.Geocoder();

    // Process drupalSettings for every Google map present on the current page.
    $.each(
      maps,

      /**
       * @param {string} mapId - map ID
       * @param {GeolocationMap} map - Geolocation map
       * @param {jQuery} map.controls - Controls
       */
      function (mapId, map) {
        if (typeof (drupalSettings.geolocation.widgetSettings[mapId]) === 'undefined') {
          drupalSettings.geolocation.widgetSettings[mapId] = [];
        }

        map.id = mapId;

        // Get the container object.
        map.container = $('#' + mapId, context).first();

        if ($(map.container).length >= 1
          && !$(map.container).hasClass('geolocation-processed')
          && typeof google !== 'undefined'
          && typeof google.maps !== 'undefined'
        ) {

          /**
           *
           * Custom event listener setup.
           *
           */

          // Execute when a location is defined by the widget.
          Drupal.geolocation.geocoderWidget.addLocationCallback(function (location) {
            Drupal.geolocation.geocoderWidget.setHiddenInputFields(location, map);
            map.controls.children('button.clear').removeClass('disabled');
            Drupal.geolocation.removeMapMarker(map);
            Drupal.geolocation.setMapMarker(map, {
              position: location,
              map: map.googleMap,
              title: location.lat() + ', ' + location.lng(),
              infoWindowContent: Drupal.t('Latitude') + ': ' + location.lat() + ' ' + Drupal.t('Longitude') + ': ' + location.lng()
            });
          }, mapId);

          // Execute when a location is unset by the widget.
          Drupal.geolocation.geocoderWidget.addClearCallback(function () {
            Drupal.geolocation.geocoderWidget.clearHiddenInputFields(map);
            map.controls.children('button.clear').addClass('disabled');
            // Clear the map point.
            Drupal.geolocation.removeMapMarker(map);
          }, mapId);

          /**
           *
           * Initialize map.
           *
           */

          // Map lat and lng are always set to user defined values or 0 initially.

          // If field values already set, use only those and set marker.
          var fieldValues = {
            lat: $('.canvas-' + mapId + ' .geolocation-hidden-lat').attr('value'),
            lng: $('.canvas-' + mapId + ' .geolocation-hidden-lng').attr('value')
          };

          if (
            typeof fieldValues.lat === 'undefined'
            && typeof fieldValues.lng === 'undefined'
          ) {
            fieldValues.lat = '';
            fieldValues.lng = '';
          }

          var setInitialMarker = false;
          var setInitialLocation = false;

          // Override map center with field values.
          if (
            !isNaN(parseFloat(fieldValues.lat))
            && !isNaN(parseFloat(fieldValues.lng))
          ) {
            map.lat = fieldValues.lat;
            map.lng = fieldValues.lng;
            setInitialMarker = true;
          }
          // If requested in settings, try to override map center by user location.
          else if (typeof (drupalSettings.geolocation.widgetSettings[mapId].autoClientLocation) !== 'undefined') {
            if (
              drupalSettings.geolocation.widgetSettings[mapId].autoClientLocation
              && navigator.geolocation
              && !drupalSettings.geolocation.widgetSettings[mapId].locationSet
            ) {
              navigator.geolocation.getCurrentPosition(function (position) {
                map.lat = position.coords.latitude;
                map.lng = position.coords.longitude;

                var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

                Drupal.geolocation.drawAccuracyIndicator(
                  location,
                  position.coords.accuracy,
                  map.googleMap
                );

                // If requested, also use location as value.
                if (typeof (drupalSettings.geolocation.widgetSettings[mapId].autoClientLocationMarker) !== 'undefined') {
                  if (drupalSettings.geolocation.widgetSettings[mapId].autoClientLocationMarker) {

                    // Map most likely already initialized.
                    if (typeof map.googleMap !== 'undefined') {
                      Drupal.geolocation.geocoderWidget.locationCallback(location, mapId);
                      if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
                        Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation(location, map);
                      }
                    }
                    else {
                      setInitialLocation = true;
                    }
                  }
                }
              });
            }
          }

          // Add the map by ID with settings.
          Drupal.geolocation.addMap(map);

          var initialLocation = new google.maps.LatLng(map.lat, map.lng);
          if (setInitialLocation) {
            Drupal.geolocation.geocoderWidget.locationCallback(initialLocation, mapId);
            if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
              Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation(initialLocation, map);
            }
          }
          // We know that fields are already correctly set, so just place the marker.
          else if (setInitialMarker) {
            Drupal.geolocation.setMapMarker(map, {
              position: initialLocation,
              map: map.googleMap,
              title: initialLocation.lat() + ', ' + initialLocation.lng(),
              infoWindowContent: Drupal.t('Latitude') + ': ' + initialLocation.lat() + ' ' + Drupal.t('Longitude') + ': ' + initialLocation.lng()
            });
            $('#geocoder-controls-wrapper-' + mapId + 'button.clear', context).removeClass('disabled');
          }

          /**
           *
           * Map controls.
           *
           */

          // Add the geocoder to the map.
          map.controls = $('#geocoder-controls-wrapper-' + mapId, context);

          map.googleMap.controls[google.maps.ControlPosition.TOP_LEFT].push(map.controls.get(0));

          map.controls.children('input.location').first().autocomplete({
            autoFocus: true,
            source: function (request, response) {
              var autocompleteResults = [];
              Drupal.geolocation.geocoderWidget.geocoder.geocode(
                  {address: request.term},

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
             * Add the click listener.
             *
             * @param {object} event - Triggered event
             * @param {object} ui - Element from autoselect field.
             * @param {GoogleAddress} ui.item.address - Googleaddress bound to autoselect result.
             */
            select: function (event, ui) {
              // Set the map viewport.
              map.googleMap.fitBounds(ui.item.address.geometry.viewport);
              Drupal.geolocation.geocoderWidget.locationCallback(ui.item.address.geometry.location, mapId);
              if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
                Drupal.geolocation.geocoderWidget.setHiddenAddressField(ui.item.address, map);
              }
            }
          });

          map.controls.submit(function (e) {
            e.preventDefault();
            Drupal.geolocation.geocoderWidget.geocoder.geocode(
              {address: map.controls.children('input.location').first().val()},

              /**
               * Google Geocoding API geocode.
               *
               * @param {GoogleAddress[]} results - Returned results
               * @param {String} status - Whether geocoding was successful
               */
              function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                  map.googleMap.fitBounds(results[0].geometry.viewport);

                  Drupal.geolocation.geocoderWidget.locationCallback(results[0].geometry.location, mapId);
                  if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
                    Drupal.geolocation.geocoderWidget.setHiddenAddressField(results[0], map);
                  }
                }
              }
            );
          });

          google.maps.event.addDomListener(map.controls.children('button.search')[0], 'click', function (e) {
            e.preventDefault();
            Drupal.geolocation.geocoderWidget.geocoder.geocode(
              {address: map.controls.children('input.location').first().val()},

              /**
               * Google Geocoding API geocode.
               *
               * @param {GoogleAddress[]} results - Returned results
               * @param {String} status - Whether geocoding was successful
               */
              function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                  map.googleMap.fitBounds(results[0].geometry.viewport);

                  Drupal.geolocation.geocoderWidget.locationCallback(results[0].geometry.location, mapId);
                  if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
                    Drupal.geolocation.geocoderWidget.setHiddenAddressField(results[0], map);
                  }
                }
              }
            );
          });

          google.maps.event.addDomListener(map.controls.children('button.clear')[0], 'click', function (e) {
            // Stop all that bubbling and form submitting.
            e.preventDefault();
            // Clear the input text.
            map.controls.children('input.location').val('');

            Drupal.geolocation.geocoderWidget.clearCallback(mapId);
          });

          // If the browser supports W3C Geolocation API.
          if (navigator.geolocation) {
            map.controls.children('button.locate').show();

            google.maps.event.addDomListener(map.controls.children('button.locate')[0], 'click', function (e) {
              // Stop all that bubbling and form submitting.
              e.preventDefault();

              // Get the geolocation from the browser.
              navigator.geolocation.getCurrentPosition(function (position) {
                var newLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

                Drupal.geolocation.drawAccuracyIndicator(
                  newLocation,
                  position.coords.accuracy,
                  map.googleMap
                );

                map.googleMap.setCenter(newLocation);

                Drupal.geolocation.geocoderWidget.locationCallback(newLocation, mapId);
                if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
                  Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation(newLocation, map);
                }
              });
            });
          }

          if (drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
            if (map.controls.children('button.address-button-locate').length) {
              google.maps.event.addDomListener(map.controls.children('button.address-button-locate')[0], 'click', function (e) {
                // Stop all that bubbling and form submitting.
                e.preventDefault();

                var targetField = drupalSettings.geolocation.widgetSettings[map.id].addressFieldTarget;
                var addressField = $('.field--type-address.field--widget-address-default.field--name-' + targetField.replace(/_/g, '-'));
                if (addressField.length < 1) {
                  return;
                }
                var addressDetails = addressField.find('.details-wrapper').first();
                if (addressDetails.length < 1) {
                  return;
                }

                var addressData = {};

                addressData.organization = addressDetails.find('.organization').val();
                addressData.addressLine1 = addressDetails.find('.address-line1').val();
                addressData.addressLine2 = addressDetails.find('.address-line2').val();
                addressData.locality = addressDetails.find('.locality').val();
                addressData.administrativeArea = addressDetails.find('.administrative-area').val();
                addressData.postalCode = addressDetails.find('.postal-code').val();

                var search = {};
                search.address = '';
                search.componentRestrictions = {};

                $.each(addressData, function (componentId, componentValue) {
                  if (componentValue) {
                    search.address += componentValue + ', ';
                  }
                });

                if (addressField.find('.country.form-select').length) {
                  search.componentRestrictions.country = addressField.find('.country.form-select').val();
                }

                Drupal.geolocation.geocoderWidget.geocoder.geocode(
                  search,

                  /**
                   * Google Geocoding API geocode.
                   *
                   * @param {GoogleAddress[]} results - Returned results
                   * @param {String} status - Whether geocoding was successful
                   */
                  function (results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                      map.googleMap.fitBounds(results[0].geometry.viewport);
                      Drupal.geolocation.geocoderWidget.locationCallback(results[0].geometry.location, mapId);
                    }
                  }
                );
              });
            }

            if (map.controls.children('button.address-button-push').length) {
              google.maps.event.addDomListener(map.controls.children('button.address-button-push')[0], 'click', function (e) {
                // Stop all that bubbling and form submitting.
                e.preventDefault();
                Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation(map.googleMap.getCenter(), map);
              });
            }
          }

          /**
           *
           * Final setup.
           *
           */

          // Add the click responders for setting the value.
          Drupal.geolocation.geocoderWidget.addClickListener(map);

          // Set the already processed flag.
          $(map.container).addClass('geolocation-processed');
        }
      }
    );
  }

  /**
   * Adds the click listeners to the map.
   *
   * @param {GeolocationMap} map - The current map object.
   */
  Drupal.geolocation.geocoderWidget.addClickListener = function (map) {
    // Used for a single click timeout.
    var singleClick;

    /**
     * Add the click listener.
     *
     * @param {GoogleMapLatLng} e.latLng
     */
    google.maps.event.addListener(map.googleMap, 'click', function (e) {
      // Create 500ms timeout to wait for double click.
      singleClick = setTimeout(function () {
        Drupal.geolocation.geocoderWidget.locationCallback(e.latLng, map.id);
        if (!drupalSettings.geolocation.widgetSettings[map.id].addressFieldExpliciteActions) {
          Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation(e.latLng, map);
        }
      }, 500);
    });

    // Add a doubleclick listener.
    google.maps.event.addListener(map.googleMap, 'dblclick', function (e) {
      clearTimeout(singleClick);
    });
  };

  /**
   * Provides the callback that is called when geocoderwidget defines a location.
   *
   * @param {GoogleMapLatLng} location - first returned address
   * @param {string} elementId - Source ID.
   */
  Drupal.geolocation.geocoderWidget.locationCallback = function (location, elementId) {
    // Ensure callbacks array;
    Drupal.geolocation.geocoderWidget.locationCallbacks = Drupal.geolocation.geocoderWidget.locationCallbacks || [];
    $.each(Drupal.geolocation.geocoderWidget.locationCallbacks, function (index, callbackContainer) {
      if (callbackContainer.elementId === elementId) {
        callbackContainer.callback(location);
      }
    });
  };

  /**
   * Adds a callback that will be called when a location is set.
   *
   * @param {geolocationGoogleGeocoderLocationCallback} callback - The callback
   * @param {string} elementId - Identify source of result by its element ID.
   */
  Drupal.geolocation.geocoderWidget.addLocationCallback = function (callback, elementId) {
    if (typeof elementId === 'undefined') {
      return;
    }
    Drupal.geolocation.geocoderWidget.locationCallbacks.push({callback: callback, elementId: elementId});
  };

  /**
   * Remove a callback that will be called when a location is set.
   *
   * @param {string} elementId - Identify the source
   */
  Drupal.geolocation.geocoderWidget.removeLocationCallback = function (elementId) {
    $.each(Drupal.geolocation.geocoderWidget.locationCallbacks, function (index, callback) {
      if (callback.elementId === elementId) {
        Drupal.geolocation.geocoderWidget.locationCallbacks.splice(index, 1);
      }
    });
  };

  /**
   * Provides the callback that is called when geocoderwidget unset the locations.
   *
   * @param {string} elementId - Source ID.
   */
  Drupal.geolocation.geocoderWidget.clearCallback = function (elementId) {
    // Ensure callbacks array;
    $.each(Drupal.geolocation.geocoderWidget.clearCallbacks, function (index, callbackContainer) {
      if (callbackContainer.elementId === elementId) {
        callbackContainer.callback(location);
      }
    });
  };

  /**
   * Adds a callback that will be called when a location is unset.
   *
   * @param {geolocationGoogleGeocoderClearCallback} callback - The callback
   * @param {string} elementId - Identify source of result by its element ID.
   */
  Drupal.geolocation.geocoderWidget.addClearCallback = function (callback, elementId) {
    if (typeof elementId === 'undefined') {
      return;
    }
    Drupal.geolocation.geocoderWidget.clearCallbacks.push({callback: callback, elementId: elementId});
  };

  /**
   * Remove a callback that will be called when a location is unset.
   *
   * @param {string} elementId - Identify the source
   */
  Drupal.geolocation.geocoderWidget.removeClearCallback = function (elementId) {
    $.each(Drupal.geolocation.geocoderWidget.clearCallbacks, function (index, callback) {
      if (callback.elementId === elementId) {
        Drupal.geolocation.geocoderWidget.clearCallbacks.splice(index, 1);
      }
    });
  };

  /**
   * Set the latitude and longitude values to the input fields
   *
   * @param {GoogleMapLatLng} latLng - A location (latLng) object from Google Maps API.
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.geocoderWidget.setHiddenInputFields = function (latLng, map) {
    // Update the lat and lng input fields.
    $('.canvas-' + map.id + ' .geolocation-hidden-lat').attr('value', latLng.lat());
    $('.canvas-' + map.id + ' .geolocation-hidden-lng').attr('value', latLng.lng());
  };

  /**
   * Set the latitude and longitude values to the input fields
   *
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.geocoderWidget.clearHiddenInputFields = function (map) {
    // Update the lat and lng input fields.
    $('.canvas-' + map.id + ' .geolocation-hidden-lat').attr('value', '');
    $('.canvas-' + map.id + ' .geolocation-hidden-lng').attr('value', '');
  };

  /**
   * Fill address field.
   *
   * @param {GoogleAddress} address - Google retrieved address object.
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.geocoderWidget.setHiddenAddressField = function (address, map) {
    if (typeof drupalSettings.geolocation.widgetSettings[map.id].addressFieldTarget === 'undefined') {
      return;
    }

    var targetField = drupalSettings.geolocation.widgetSettings[map.id].addressFieldTarget;
    var addressField = $('.field--type-address.field--widget-address-default.field--name-' + targetField.replace(/_/g, '-'));

    if (addressField.length < 1) {
      return;
    }

    var addressDetails = addressField.find('.details-wrapper').first();

    var addressLine1 = '';
    var addressLine2 = '';
    var postalTown = '';
    var countryCode = null;
    var postalCode = null;
    var streetNumber = null;
    var neighborhood = null;
    var premise = null;
    var route = null;
    var locality = null;
    var administrativeArea = null;
    var political = null;

    $.each(address.address_components, function (key, value) {
      var component = address.address_components[key];
      var types = component.types;

      switch (types[0]) {
        case 'country':
          countryCode = component.short_name;
          break;
        case 'postal_town':
          postalTown = component.long_name;
          break;
        case 'postal_code':
          postalCode = component.long_name;
          break;
        case 'street_number':
          streetNumber = component.long_name;
          break;
        case 'neighborhood':
          neighborhood = component.long_name;
          break;
        case 'premise':
          premise = component.long_name;
          break;
        case 'political':
          political = component.long_name;
          break;
        case 'route':
          route = component.long_name;
          break;
        case 'locality':
          locality = component.long_name;
          break;
        case 'administrative_area_level_1':
          administrativeArea = component.short_name;
          break;
      }
    });

    // See https://github.com/commerceguys/addressing/issues/73 for reason.

    if (streetNumber) {
      if (countryCode === 'DE') {
        addressLine1 = route + ' ' + streetNumber;
      }
      else {
        addressLine1 = streetNumber + ' ' + route;
      }
    }
    else if (route) {
      addressLine1 = route;
    }
    else if (premise) {
      addressLine1 = premise;
    }

    if (locality && postalTown && locality !== postalTown) {
      addressLine2 = locality;
    }
    else if (!locality && neighborhood) {
      addressLine2 = neighborhood;
    }

    if (postalTown) {
      locality = postalTown;
    }

    if (!locality && political) {
      // NYC. Americans are weired.
      locality = political;
    }

    if (addressField.find('.country.form-select').length) {
      // Set the country.
      addressField.find('.country.form-select').val(countryCode).trigger('change');

      $(document).ajaxComplete(function (event, xhr, settings) {
        // Update after AJAX replacement.
        addressDetails = addressField.find('.details-wrapper').first();

        if (addressDetails.length < 1) {
          return;
        }

        if (
          settings.extraData._drupal_ajax
          && settings.extraData._triggering_element_name === targetField + '[0][address][country_code]'
        ) {
          // Populate the address fields, once they have been added to the DOM.
          addressDetails.find('.organization').val(premise);
          addressDetails.find('.address-line1').val(addressLine1);
          addressDetails.find('.address-line2').val(addressLine2);
          addressDetails.find('.locality').val(locality);

          var administrativeAreaInput = addressDetails.find('.administrative-area');
          if (administrativeAreaInput) {
            if (administrativeAreaInput.prop('tagName') === 'INPUT') {
              administrativeAreaInput.val(countryCode + '-' + administrativeArea);
            }
            else if (administrativeAreaInput.prop('tagName') === 'SELECT') {
              administrativeAreaInput.val(administrativeArea);
            }
          }
          addressDetails.find('.postal-code').val(postalCode);
        }
      });
    }
    else {
      if (addressDetails.length < 1) {
        return;
      }

      // Populate the address fields, once they have been added to the DOM.
      addressDetails.find('.organization').val(premise);
      addressDetails.find('.address-line1').val(addressLine1);
      addressDetails.find('.address-line2').val(addressLine2);
      addressDetails.find('.locality').val(locality);

      var administrativeAreaInput = addressDetails.find('.administrative-area');
      if (administrativeAreaInput) {
        if (administrativeAreaInput.prop('tagName') === 'INPUT') {
          administrativeAreaInput.val(countryCode + '-' + administrativeArea);
        }
        else if (administrativeAreaInput.prop('tagName') === 'SELECT') {
          administrativeAreaInput.val(administrativeArea);
        }
      }
      addressDetails.find('.postal-code').val(postalCode);
    }
  };

  /**
   * Fill address field by reverse geocoding.
   *
   * @param {GoogleMapLatLng} location - Google location.
   * @param {GeolocationMap} map - The settings object that contains all of the necessary metadata for this map.
   */
  Drupal.geolocation.geocoderWidget.setHiddenAddressFieldByReverseLocation = function (location, map) {
    if (typeof drupalSettings.geolocation.widgetSettings[map.id].addressFieldTarget === 'undefined') {
      return;
    }
    var targetField = drupalSettings.geolocation.widgetSettings[map.id].addressFieldTarget;
    var addressField = $('.field--type-address.field--widget-address-default.field--name-' + targetField.replace(/_/g, '-'));

    if (addressField.length < 1) {
      return;
    }

    if (typeof Drupal.geolocation.geocoderWidget.geocoder === 'undefined') {
      return;
    }

    Drupal.geolocation.geocoderWidget.geocoder.geocode(
      {location: location},

      /**
       * Google Geocoding API geocode.
       *
       * @param {GoogleAddress[]} results - Returned results
       * @param {String} status - Whether geocoding was successful
       */
      function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
          Drupal.geolocation.geocoderWidget.setHiddenAddressField(results[0], map);
        }
      }
    );
  };

})(jQuery, Drupal, drupalSettings);
