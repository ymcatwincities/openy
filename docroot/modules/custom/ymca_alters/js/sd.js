;(function ($) {
  'use strict';
  Drupal.behaviors.schedules_date = {
    attach: function(context, settings) {
      var d = new Date(),
          out = [];

      out[0] = d.getMonth() + 1;
      out[1] = d.getDate();
      out[2] = d.getFullYear().toString().slice(-2);

      // Add leading zero if needed.
      if (out[1].toString().length == 1) {
        out[1] = '0' + out[1].toString();
      }

      $('#edit-filter-date-date').val(out.join('/'));
    }
  };
})(jQuery);
