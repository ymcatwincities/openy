/**
 * @file
 *   Javascript for the Geolocation HTML5 widget.
 */

(function ($, Drupal, navigator) {

  'use strict';

  /**
   * Attach html5 widget functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches html5 widget functionality to relevant elements.
   */
  Drupal.behaviors.geolocationHTML5 = {
    attach: function (context, settings) {
      $('.geolocation-html5-button:not(.disabled)').each(function (index) {
        // The parent element.
        var $thisButton = $(this);

        // Set the values of hidden form inputs.
        var latDefault = $thisButton.siblings('.geolocation-hidden-lat').val();
        var lngDefault = $thisButton.siblings('.geolocation-hidden-lng').val();

        if (latDefault.length > 1 && lngDefault.length > 1) {
          // Hide the default text.
          $('.default', $thisButton).hide();

          // Display a location.
          var locationString = Drupal.t('Browser location: @lat,@lng', {'@lat': latDefault, '@lng': lngDefault});
          $('.location', $thisButton).html(locationString);

          // Disable the button.
          $thisButton.addClass('disabled');

          // Show the clear icon.
          $('.clear', $thisButton).show();
        }

      });

    }
  };

  $('.geolocation-html5-button .clear').on('click', function (event) {
    // The parent element.
    var $thisButton = $(this).parent();

    // Prevent form submission.
    event.stopPropagation();

    // Clear the values of hidden form inputs.
    $thisButton.siblings('.geolocation-hidden-lat').val('');
    $thisButton.siblings('.geolocation-hidden-lng').val('');

    // Show the default text.
    $('.default', $thisButton).show();

    // Clear the location message.
    $('.location', $thisButton).html('');

    // Hide the clear icon.
    $('.clear', $thisButton).hide();

    // Enable the button.
    $thisButton.removeClass('disabled');

  });

  $('.geolocation-html5-button').on('click', function (event) {
    // The parent element.
    var $thisButton = $(this);

    if ($thisButton.hasClass('disabled')) {
      return;
    }

    // If the browser supports W3C Geolocation API.
    if (navigator.geolocation) {

      // Get the geolocation from the browser.
      navigator.geolocation.getCurrentPosition(

        // Success handler for getCurrentPosition()
        function (position) {
          var lat = position.coords.latitude;
          var lng = position.coords.longitude;
          var accuracy = position.coords.accuracy / 1000;

          // Set the values of hidden form inputs.
          $thisButton.siblings('.geolocation-hidden-lat').val(lat);
          $thisButton.siblings('.geolocation-hidden-lng').val(lng);

          // Hide the default text.
          $('.default', $thisButton).hide();

          // Display a success message.
          var locationString = Drupal.t('Browser location: @lat,@lng Accuracy: @accuracy m', {'@lat': lat, '@lng': lng, '@accuracy': accuracy});
          $('.location', $thisButton).html(locationString);

          // Disable the button.
          $thisButton.addClass('disabled');

          // Show the clear icon.
          $('.clear', $thisButton).show();
        },

        // Error handler for getCurrentPosition()
        function (error) {

          // Alert with error message.
          switch (error.code) {
            case error.PERMISSION_DENIED:
              alert(Drupal.t('No location data found. Reason: PERMISSION_DENIED.'));
              break;
            case error.POSITION_UNAVAILABLE:
              alert(Drupal.t('No location data found. Reason: POSITION_UNAVAILABLE.'));
              break;
            case error.TIMEOUT:
              alert(Drupal.t('No location data found. Reason: TIMEOUT.'));
              break;
            default:
              alert(Drupal.t('No location data found. Reason: Unknown error.'));
              break;
          }
        },

        // Options for getCurrentPosition()
        {
          enableHighAccuracy: true,
          timeout: 5000,
          maximumAge: 6000
        }
      );

    }
    else {
      alert(Drupal.t('No location data found. Your browser does not support the W3C Geolocation API.'));
    }
  });

})(jQuery, Drupal, navigator);
