(function ($, Drupal) {
  Drupal.behaviors.repeat_locations = {
    attach: function attach(context, settings) {
      $('.js-submit-locations').click(function() {
        var chkArray = [];
        $(".js-locations-row .js-location-box").each(function() {
          if ($(this).is(':checked')) {
            chkArray.push(this.value);
          }
        });
        location.href = '/schedules/group-excercise-classes/' + chkArray.join(',');
      });
    }
  };
})(jQuery, Drupal);