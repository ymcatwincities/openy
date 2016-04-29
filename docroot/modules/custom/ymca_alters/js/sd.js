;(function ($) {
    'use strict';
    Drupal.behaviors.schedules_date = {
        attach: function(context, settings) {
            var d = new Date();
            var out = "";
            var date = d.getDate();
            var month = d.getMonth();
            var year = d.getFullYear();
            var findate = out.concat(month+1, "/", date, "/", year.toString().slice(-2));
            $('#edit-filter-date-date').val(findate);
        }
    };

})(jQuery);