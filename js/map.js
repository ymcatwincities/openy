/**
 * @file main.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.openyMap = function() {
    return {
      drawMap: function (config) {
        $(config.element).once('openyMap').each(function () {

          // Let's center map on the first point.
          var map = new google.maps.Map($(config.element).get(0), {
            zoom: 13,
            center: config.data[0]
          });


          // Add markers.
          $.each(config.data, function(index, value) {
            var marker = new google.maps.Marker({
              position: { lat: value.lat, lng: value.lng },
              map: map,
              title: value.title
            });

            var info = new google.maps.InfoWindow({
              content: value.markup
            });

            marker.addListener('click', function() {
              info.open(map, marker);
            });
          });

        });
      }
    }
  };

  Drupal.behaviors.openyMap = {
    attach: function (context, settings) {
      var data = settings.openyMap;
      var map = new Drupal.openyMap();

      map.drawMap({
          element: '.openy-map-canvas',
          data: data
      });

    }
  };

}(jQuery, Drupal, drupalSettings));
