(function($) {

  window.onpopstate = function(event) {
    window.location.reload();
  };

  $.fn.groupExLocationAjaxAction = function(location_id) {
    history.pushState(null, null, '/all_y_schedules?location=' + location_id);
  }

  Drupal.behaviors.ymca_groupex = {
    attach: function (context, settings) {

    }
  };

})(jQuery);
