/**
 * @file main.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  // pass context
  Drupal.openyMap = function() {
    return {
      drawMap: function (config) {
        // Let's center map on the first point.
        var map = new google.maps.Map(config.element, {
          zoom: 10,
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
      }
    }
  };

  Drupal.behaviors.openyMap = {
    attach: function (context, settings) {
      var data = settings.openyMap;
      var map = new Drupal.openyMap();

      $('.openy-map-canvas', context).once().each(function () {
        var self = this;
        var timer = setInterval(function () {
          if (typeof window.google == 'undefined') {
            return;
          }

          map.drawMap({
            element: self,
            data: data
          });

          clearInterval(timer);
        }, 100);
      });

    }
  };

}(jQuery, Drupal, drupalSettings));
