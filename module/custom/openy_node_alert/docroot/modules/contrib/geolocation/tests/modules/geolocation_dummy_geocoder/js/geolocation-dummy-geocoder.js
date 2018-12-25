/**
 * @file
 *   Javascript for the Dummy geocoder.
 */

(function ($, Drupal) {
  'use strict';

  if (typeof Drupal.geolocation.geocoder === 'undefined') {
    return false;
  }

  /**
   * Attach geocoder input for Dummy
   */
  Drupal.behaviors.geolocationGeocoderDummy = {
    attach: function (context) {
      $('input.geolocation-geocoder-dummy', context).once().on('input', function () {
        var that = $(this);
        Drupal.geolocation.geocoder.clearCallback(that.data('source-identifier'));

        if (!that.val().length) {
          return;
        }
        $('.geolocation-geocoder-dummy-state[data-source-identifier="' + that.data('source-identifier') + '"]').val(0);

        $.ajax(drupalSettings.path.baseUrl + 'geolocation_dummy_geocoder/geocode/' + that.val()).done(function (data) {
          if (data.length < 3) {
            return;
          }
          var address = {
            geometry: {
              location: {
                lat: function () {
                  return data.location.lat;
                },
                lng: function () {
                  return data.location.lng;
                }
              }
            }
          };
          Drupal.geolocation.geocoder.resultCallback(address, that.data('source-identifier'));
          $('.geolocation-geocoder-dummy-state[data-source-identifier="' + that.data('source-identifier') + '"]').val(1);
        });
      });
    }
  };

})(jQuery, Drupal);
