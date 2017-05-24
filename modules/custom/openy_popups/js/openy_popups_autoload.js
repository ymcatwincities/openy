(function ($, Drupal, drupalSettings) {

  'use strict';

  // Pops the location selec box up on page load.
  Drupal.behaviors.openy_popups_autoload = {
    attach: function (context, settings) {
      // How to set preferred branch:
      // $.cookie('openy_preferred_branch', 6, { expires: 7, path: '/' });

      var preferred_branch = $.cookie('openy_preferred_branch');
      if (typeof this.get_query_param().location == 'undefined' && typeof preferred_branch == 'undefined') {
        // Open popup.
        $('a.location-popup-link').once().click();
        $(document).on('click', 'body > .ui-widget-overlay', function() {
          return false;
        });
      }
    },

    // Extracts query params from url.
    get_query_param: function () {
      var query_string = {};
      var query = window.location.search.substring(1);
      var pairs = query.split('&');
      for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');

        // If first entry with this name.
        if (typeof query_string[pair[0]] === 'undefined') {
          query_string[pair[0]] = decodeURIComponent(pair[1]);
        }
        // If second entry with this name.
        else if (typeof query_string[pair[0]] === 'string') {
          query_string[pair[0]] = [
            query_string[pair[0]],
            decodeURIComponent(pair[1])
          ];
        }
        // If third or later entry with this name
        else {
          query_string[pair[0]].push(decodeURIComponent(pair[1]));
        }
      }

      return query_string;
    }
  };

} (jQuery, Drupal, drupalSettings));
