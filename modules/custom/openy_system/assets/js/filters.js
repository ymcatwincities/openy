(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_filters = {
    attach: function (context, settings) {

      // +/- Toggle.
      $('.schedule-dashboard__sidebar .navbar-header a[data-toggle], .form-group-wrapper label[data-toggle]').on('click', function() {
        if (!$('.' + $(this).attr('for')).hasClass('collapsing')) {
          $(this)
            .toggleClass('closed active')
            .find('i')
            .toggleClass('fa-minus fa-plus');
        }
      });

    }
  };

})(jQuery, Drupal);