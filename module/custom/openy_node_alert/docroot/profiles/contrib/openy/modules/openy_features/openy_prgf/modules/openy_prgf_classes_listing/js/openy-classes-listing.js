(function ($, Drupal, drupalSettings) {

  /**
   * Break down query string and build an array.
   *
   * @param url string
   *   String url, with possible parameters.
   * @param base {}
   *   Object to return base.url without parameters.
   *
   * @returns {Array}
   */
  function getUrlParams(url, base) {
    var query_array = [], hash;
    var link_url_split = url.split('?');
    var param_string = link_url_split[1];
    base.url = link_url_split[0];
    if(param_string !== undefined){
      params = param_string.split('&');
      for(var i = 0; i < params.length; i++){
        hash = params[i].split('=');
        value = decodeURI(hash[1]);
        query_array[decodeURI(hash[0])] = value;
      }
    }

    return query_array;
  }


  /**
   * Set location from cookie openy_preferred_branch, and attach location list
   * item links.
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.openy_classes_listing_form = {
    attach: function(context, settings) {

      $('#views-exposed-form-classes-listing-search-form')
        .each(function() {
          var $self = $(this);
          var selected_location = $self.find('.js-form-item-location select').val();
          if (selected_location && selected_location !== 'All') {
            // Set result items links location parameter.
            $('.activity-group-slider a').each(function() {
              var href = $(this).attr('href');
              var query_params = [], base = {};
              var query_array = getUrlParams(href, base);
              // Overwrite location with selected_location.
              query_array['location'] = selected_location;

              // Concatenate query names & vals.
              for (var key in query_array) {
                query_params.push(key + '=' + query_array[key]);
              }

              // Reconstruct the link URL and write to DOM.
              $(this).attr('href', base.url + '?' + query_params.join('&'));
            });
          }
          setTimeout(function() {
            // When an openy_preferred_branch cookie is present and the location
            // is not set then set location from the cookie and submit the form.
            var preferred_branch = $.cookie('openy_preferred_branch')
              , pagebase = {}
              , query = getUrlParams(window.location.href, pagebase)
              , location = query['location'];
            if (typeof location === 'undefined' && typeof preferred_branch !== 'undefined') {
              $self.find('.js-form-item-location select').val(preferred_branch);
              $self.submit();
            }
            if (!$self.hasClass('filter-was-applied')) {
              $self.addClass('filter-was-applied');
              $self.find('.form-submit').click();
            }
          }, 0);
        });
    }
  };

  Drupal.behaviors.openy_prgf_class_listing_removeUnneededAria = {
    attach: function (context, settings) {
      $('.activity-group-slider .slick-slide', context).once().each(function () {
        $(this).removeAttr('role');
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
