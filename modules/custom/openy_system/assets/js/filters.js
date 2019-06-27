(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_filters = {
    attach: function (context, settings) {

      // +/- Toggle.
      $('.schedule-dashboard__sidebar .navbar-header a[data-toggle], .form-group-wrapper label[data-toggle]', context).on('click', function() {
        if (!$('.' + $(this).attr('for')).hasClass('collapsing')) {
          switch ($(this).attr('for')) {
            case 'form-group-classname':
            case 'form-group-instructors':
              $(this)
                  .find('i')
                  .toggleClass('fa-plus fa-minus');
              break;
          }
          $(this)
              .toggleClass('closed active')
              .find('i')
              .toggleClass('fa-plus fa-minus');
        }
      });

    }
  };

})(jQuery, Drupal);