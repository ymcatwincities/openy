(function($) {

  window.onpopstate = function(event) {
    window.location.reload();
  };

  $.fn.groupExLocationAjaxAction = function(parameters) {
    var params = [];
    for (key in parameters) {
      if (key !== '_wrapper_format') {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.pushState(null, null, drupalSettings.path.baseUrl + 'all_y_schedules?' + params.join('&'));
    // Form widgets hide/show.
    $('#location-select-wrapper').removeClass('hidden');
    $('#date-select-wrapper').removeClass('hidden');
    $('#location-wrapper').addClass('hidden');
  }

  Drupal.behaviors.ymca_groupex = {
    attach: function (context, settings) {

    }
  };

})(jQuery);
