/**
 * @file main.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.openyMap = function() {
    return {
      // Array of location data.
      locations: null,
      // URL of marker image.
      marker_image_url: null,
      // URL of shadow image.
      shadow_image_url: null,
      // The Map object.
      map: null,
      // Array of tag data, keyed by tag name.
      tags: {},
      // The distance filter limit, in miles.
      distance_limit: null,
      // The center point of the map.
      center_point: null,
      // The center point of a search location or distance limit.
      search_center_point: null,
      // Marker designating the center point.
      search_center_marker: null,
      // Geocoder.
      geocoder: typeof google !== 'undefined' ? new google.maps.Geocoder() : {},
      // Normalizes a map-vendor specicific representation of
      // a coordinate point to a {lat:x, lon:y} object.
      normalize_point: function (point) {
        return {
          'lat': point.lat(),
          'lon': point.lng()
        };
      },

      // Convert a number from degrees to radians.
      toRad: function (n) {
        return n * Math.PI / 180;
      },

      init: function (args) {
        this.component_el = args.component_el;

        this.map_data = args.map_data;
        this.locations = this.map_data;

        this.marker_image_url = args.marker_image_url || null;
        this.shadow_image_url = args.shadow_image_url || null;

        this.search_center_marker = args.search_center_marker || null;

        this.map_el = this.component_el.find('.openy-map');
        this.messages_el = this.component_el.find('.messages');

        this.map_controls_el = this.component_el.find('.map_controls');
        this.search_field_el = this.map_controls_el.find('input.search_field');
        this.distance_limit_el = this.map_controls_el.find('select.distance_limit_value');
        this.locate_me_el = this.map_controls_el.find('.locateme');

        this.tags = {};

        this.init_map();
        this.init_tags();
        this.init_map_locations();
        this.draw_map_controls();
        this.hookup_map_controls_events();
        this.update_tag_filters();
        this.draw_map_locations();
        this.draw_list_locations();

        var mapLocation = document.location.href.match(/&?[amp;]?map_location=([\w|\+]*)&?[amp;]?/),
            component = this;

        if (!navigator.geolocation) {
          $('.with-geo').remove();
        }
        this.component_el.find('.zip-code .btn-submit')
            .on('click', $.proxy(this.apply_search, this));

        this.search_field_el.on('keypress', function (e) {
          if (e.keyCode == 13) component.apply_search();
        });
        if (mapLocation) {
          $('.search_field')
              .val(mapLocation[1].replace(/\+/g, ' '));

          $('.distance_limit option').eq(2).attr('selected', true);
          $('.zip-code .btn-submit').click();
        }
      },

      // Initializes the base map.
      init_map: function () {
        this.map = new google.maps.Map(this.map_el[0], {
          scaleControl: true,
          center: this.center,
          zoom: 9,
          scrollwheel: false,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        this.init_map_center();

        this.component_el.trigger('initialized', [this.map]);
      },

      init_map_center: function () {
        this.search_center_marker = this.search_center_marker || new google.maps.Marker({
              position: this.center_point,
              animation: google.maps.Animation.DROP
            });

        if (this.search_center_marker) {
          this.search_center_marker.setVisible(false);
          this.search_center_marker.setMap(this.map);
        }
      },

      // Executed every time a checkbox filter state changes.
      filter_change: function (evt) {
        if (evt) {
          var evt_target = $(evt.currentTarget);
          var matching_bar_checkbox = this.component_el.find('nav.types input.' + evt_target.attr('class') + '[type=checkbox]');
          matching_bar_checkbox.prop('checked', evt_target.prop('checked'));
          matching_bar_checkbox.parents('label').toggleClass('checked', evt_target.prop('checked'));
        }

        this.update_tag_filters();
        this.redraw_map_locations();
        this.draw_list_locations();
      },

      // Attaches events to various map controls
      hookup_map_controls_events: function () {
        this.map_controls_el.find('.tag_filters input[type=checkbox]').on('change', $.proxy(this.filter_change, this));
        this.search_field_el.on('change', $.proxy(this.apply_search, this));
        this.distance_limit_el.on('change', $.proxy(this.apply_distance_limit, this));
        this.locate_me_el.on('click', $.proxy(this.locate_me_onclick, this));

        this.component_el.find('nav.types input[type=checkbox]').on('change', $.proxy(this.bar_filter_change, this));
      },

      // Attempts a map search against Google's
      // GeoCoding API.  If successful, the map
      // is recentered according to the result.
      apply_search: function () {
        var q = this.search_field_el.val();
        var f = function (results, status) {
          if (status == 'OK') {
            this.search_center_point = results[0].geometry.location;
            if (results[0].geometry.bounds) {
              this.map.fitBounds(results[0].geometry.bounds);
            } else {
              var bounds = new google.maps.LatLngBounds();
              bounds.extend(this.search_center_point);
              this.map.fitBounds(bounds);
            }
            this.search_center = this.map.getCenter();
            this.draw_search_center();
            this.apply_distance_limit();
          }
        };

        this.geocoder.geocode({
          'address': q
        }, $.proxy(f, this));
      },

      // Executed every time the viewer sets the distance limit to a new value
      apply_distance_limit: function () {
        if (this.search_center === null) {
          this.search_center = this.map.getCenter();
        }
        this.distance_limit = this.distance_limit_el.val();

        this.draw_search_center();
        this.redraw_map_locations();
        this.draw_list_locations();
      },

      locate_me_onclick: function (evt) {
        if (!navigator.geolocation) {
          return;
        }

        this.search_field_el.val('');
        this.geolocation_watcher = navigator.geolocation.watchPosition($.proxy(this.locate_me, this));
      },

      locate_me: function (position) {
        var lat = position.coords.lat;
        var lng = position.coords.lng;

        this.search_center_point = new google.maps.LatLng(lat, lng);

        this.map.setCenter(this.search_center_point);
        this.map.setZoom(14);
        if (position.coords.accuracy <= 15840) { // 3 miles

          this.geocoder.geocode({
                'latLng': this.search_center_point
              },
              $.proxy(
                  function (results, status) {
                    if (results[0]) this.search_field_el.val(results[0].formatted_address);
                    this.apply_search();
                  },
                  this));

          navigator.geolocation.clearWatch(this.geolocation_watcher);
        }

        this.draw_search_center();
      },

      // Extracts unique tag values from the map location data.
      init_tags: function () {
        // Extract tags.
        for (var i = 0; i < this.locations.length; i++) {
          var loc = this.locations[i];
          if (!loc.tags) {
            loc.tags = [];
          }

          // Convert single-string tags to array
          if (typeof(loc.tags) == typeof( "" )) {
            loc.tags = [loc.tags];
          }

          for (var j = 0; j < loc.tags.length; j++) {
            var tag = loc.tags[j];
            if (!( tag in this.tags )) {
              this.tags[tag] = {'marker_icons': []};
            }
            if (loc.icon && $.inArray(loc.icon, this.tags[tag].marker_icons) == -1) {
              this.tags[tag].marker_icons.push(loc.icon);
            }
          }
        }
      },

      // Applies the current checkbox state of the tag filter controls
      // to the internal filters data structure.
      // Called at init time, and after every checkbox state change.
      update_tag_filters: function () {
        this.tag_filters = [];
        var self = this;

        var f = function (index) {
          var el = $(this);
          self.tag_filters.push(el.val());
        };

        this.map_controls_el.find('.tag_filters input[type=checkbox]:checked').each(f);
      },

      // Applies tag and distance filters to a list of locations,
      // returns the filtered list.
      apply_filters: function (locations) {
        locations = this.apply_tag_filters(locations);
        locations = this.apply_distance_filters(locations);
        return locations;
      },

      // Applies tag filters to a list of locations,
      // returns the filtered list.
      apply_tag_filters: function (locations) {
        if (this.tag_filters.length === 0) {
          return locations;
        }

        var filtered_locations = [];
        for (var i = 0; i < locations.length; i++) {
          var loc = locations[i];
          for (var j = 0; j < this.tag_filters.length; j++) {
            var tag_filter = this.tag_filters[j];
            if ($.inArray(tag_filter, loc.tags) >= 0) {
              filtered_locations.push(loc);
              continue;  // If any tag matches, skip checking other tags
            }
          }
        }
        return filtered_locations;
      },

      // Applies distance filters to a list of locations,
      // returns the filtered list.
      apply_distance_filters: function (locations) {
        if (!this.search_center) {
          return locations;
        }

        if (!this.distance_limit || this.distance_limit === '') {
          return locations;
        }

        var search_center = this.normalize_point(this.search_center);
        var filtered_locations = [];

        var lat1 = parseFloat(search_center.lat);
        var lon1 = parseFloat(search_center.lon);
        var rlat1 = this.toRad(lat1);

        for (var i = 0; i < locations.length; i++) {
          var loc = locations[i];
          var R = 3963,
              lat2 = parseFloat(loc.lat),
              lon2 = parseFloat(loc.lng);

          var rlat = this.toRad(lat2 - lat1);
          var rlon = this.toRad(lon2 - lon1);
          var rlat2 = this.toRad(lat2);

          var a = Math.sin(rlat / 2) * Math.sin(rlat / 2) + Math.sin(rlon / 2) * Math.sin(rlon / 2) * Math.cos(rlat1) * Math.cos(rlat2);
          var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
          var d = R * c;

          if (d <= this.distance_limit) {
            // Add the distance to the object
            loc.distance = d;
            filtered_locations.push(loc);
          }
        }

        return filtered_locations;
      },

      // Populates an array of active tags from an URL parameter "map_tag_filter"
      init_active_tags: function () {
        if (this.initial_active_tags) {
          return this.initial_active_tags;
        }

        var active_tags = [];
        var url_parameters = this.get_parameters();
        var tag_filter_url_value = url_parameters.map_tag_filter;

        var tag_filter_url_values = ( tag_filter_url_value ) ? tag_filter_url_value.split(",") : [];

        for (var tag in this.tags) {
          if (tag_filter_url_values.length === 0) {
            active_tags.push(tag);
          }
          else if ($.inArray(tag, tag_filter_url_values) >= 0) {
            active_tags.push(tag);
          }
        }

        this.initial_active_tags = active_tags;

        if (tag_filter_url_values.length === 0) {
          this.initial_active_tags = ['YMCA'];
        }
      },

      get_parameters: function () {
        var searchString = window.location.search.substring(1);
        var params = searchString.split("&");
        var hash = {};

        for (var i = 0; i < params.length; i++) {
          var val = params[i].split("=");
          hash[unescape(val[0])] = unescape(val[1]);
        }
        return hash;
      },

      // Renders an extra set of filter boxes below the map.
      draw_map_controls: function () {

        this.init_active_tags();

        var tag_filters_html = '';

        for (var tag in this.tags) {

          var filter_checked = '';

          if ($.inArray(tag, this.initial_active_tags) >= 0) {
            filter_checked = 'checked="checked"';
          }

          var tag_filter_html = '<label class="btn btn-default" for="tag_' + tag + '">';

          tag_filter_html += '<input autocomplete="off" id="tag_' + tag + '" class="tag_' + tag + '" type="checkbox" value="' + tag + '" ' + filter_checked + '/>' + tag;

          for (var i = 0; i < this.tags[tag].marker_icons.length; i++) {
            tag_filter_html += '<img class="tag_icon inline-hidden-sm" src="' + this.tags[tag].marker_icons[i] + '"/>';
          }

          tag_filter_html += '</label>';

          tag_filters_html += tag_filter_html;
        }

        this.map_controls_el.find('.tag_filters').append(tag_filters_html);

      },

      // Update locations on the map by setting their visiblity
      // and refit the map bounds to the current set of visible locations
      draw_map_locations: function () {
        var locations = this.apply_filters(this.locations);

        // If the location list is empty, don't adjust the map at all
        if (locations.length === 0) {
          this.map.setCenter(this.search_center_point);
          return;
        }

        var bounds = new google.maps.LatLngBounds();

        for (var i = 0; i < locations.length; i++) {
          var loc = locations[i];
          bounds.extend(loc.marker.getPosition());
          loc.marker.setVisible(true);
        }

        this.map.fitBounds(bounds);

      },

      // Updates locations on the map by setting their visibility
      // to false before drawing.
      redraw_map_locations: function () {
        for (var i = 0; i < this.locations.length; i++) {
          var loc = this.locations[i];
          loc.marker.setVisible(false);
        }

        this.draw_map_locations();
      },

      // Render the list of locations.
      draw_list_locations: function () {

        var list_locations_html = '',
            locations = this.apply_filters(this.locations);

        // Hide all locations.
        for (var l = 0; l < this.locations.length; l++) {
          if (typeof this.locations[l].element !== 'undefined') {
            this.locations[l].element.hide();
            $(this.locations[l].element).parents('.locations-list').find('h1').hide();
          }
        }

        if (!locations.length) {
          this.messages_el.hide().html('<div class="col-xs-12 text-center"><p>We\u2019re sorry no results were found in your area</p></div>').fadeIn();
          return;
        }

        // Show filtered locations.
        for (var k = 0; k < locations.length; k++) {
          if (typeof locations[k].element !== 'undefined') {
            locations[k].element.show();
            $(locations[k].element).parents('.locations-list').find('h1').show();
          }
        }
      },

      // Generate the HTML for a single location in the list.
      draw_list_location: function (loc) {
        return loc.markup;
      },

      // Generate the HTML for a single location's map detail view
      draw_map_location: function (loc) {
        return this.draw_list_location(loc);
      },

      init_map_locations: function () {
        var self = this;
        var locations = this.locations;

        var f = function (infowindow, marker) {
          return function () {
            for (var i = 0; i < self.locations.length; i++) {
              self.locations[i].infowindow.close();
            }
            infowindow.open(this.map, marker);
          };
        };

        for (var i = 0; i < locations.length; i++) {
          var loc = locations[i];
          loc.point = new google.maps.LatLng(loc.lat, loc.lng);
          var html = '<div class="marker_tooltip">' + this.draw_map_location(loc) + '</div>';
          var marker_anchor = new google.maps.MarkerImage(this.marker_image_url) || null;
          marker_anchor = loc.icon ? new google.maps.MarkerImage(loc.icon) : marker_anchor;
          var shadow_anchor = loc.shadow ? new google.maps.MarkerImage(loc.shadow) : null;

          var marker = new google.maps.Marker({
            position: loc.point,
            icon: marker_anchor,
            shadow: shadow_anchor,
            animation: google.maps.Animation.DROP
          });

          var infowindow = new google.maps.InfoWindow({
            content: html
          });

          loc.infowindow = infowindow;

          google.maps.event.addListener(marker, 'click', f(infowindow, marker));

          marker.setVisible(false);
          marker.setMap(this.map);
          loc.marker = marker;
        }
      },

      draw_search_center: function () {
        this.search_center_marker.setPosition(this.search_center_point);
        this.search_center_marker.setVisible(true);
      },

      // Executed every time a checkbox bar filter state changes.
      bar_filter_change: function (evt) {
        var evt_target = $(evt.currentTarget);
        var matching_checkbox = this.map_controls_el.find('input.' + evt_target.attr('class') + '[type=checkbox]');

        // Uncheck all checkboxes.
        this.map_controls_el.find('input[type=checkbox]').prop('checked', false);
        this.component_el.find('nav.types input[type=checkbox]').prop('checked', false);
        this.component_el.find('nav.types label').removeClass('checked');

        // Check the just-clicked one.
        matching_checkbox.prop('checked', true);
        evt_target.prop('checked', true);

        evt_target.parents('label').addClass('checked');
        this.filter_change();
      },

      build_google_url: function (str) {
        str = str.trim();
        str = str.replace(/ /g, "+");

        if (str.length > 0) {
          str += '+';
        }
        return str;
      }
    };
  };

  Drupal.behaviors.openyMap = {
    attach: function (context, settings) {
      var data = settings.openyMap;
      var map = new Drupal.openyMap();

      $('.locations-list .node--view-mode-teaser').each(function() {
        var $self = $(this);
        for (var i = 0; i < data.length; i++) {
          if (typeof(data[i]) !== 'undefined' && $self.find("h2")[0].innerText !== 'undefined') {
            if ($self.find("h2")[0].innerText == data[i]["name"]){
              data[i].element = {};
              data[i].element = $self.parent();
            }
          }
        };
      });

      $('.openy-map-canvas', context).once().each(function () {
        var timer = setInterval(function () {
          if (typeof window.google == 'undefined') {
            return;
          }

          map.init({
            component_el: $('.openy-map-wrapper'),
            map_data: data
          });

          // Reset openyMap data (fix for old pins on new map after ajax call).
          settings.openyMap = [];
          clearInterval(timer);
        }, 100);
      });

    }
  };

}(jQuery, Drupal, drupalSettings));
