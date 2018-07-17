(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_locations = {
    attach: function (context, settings) {

      // Toggle active class on location item.
      $('.schedule-locations__item input', context).once('openy-submit-locations').on('change', function() {
        if(!$(this).parent().hasClass('selected')) {
          $(this).parent('label').attr('class', 'selected');
        }
        else {
          $(this).parent().removeClass('selected');
        }

        // Toggle disable the submit button.
        if($('.schedule-locations__item label.selected').length > 0) {
          $('.js-submit-locations', context).removeClass('disabled');
        } else {
          $('.js-submit-locations', context).addClass('disabled');
        }
      });

      // Attach location arguments to url on submit.
      $('.js-submit-locations', context).once('openy-submit-locations').click(function() {
        var chkArray = [];
        $(".js-locations-row .js-location-box").each(function() {
          if ($(this).is(':checked')) {
            chkArray.push(this.value);
          }
        });
        location.href = '/schedules/group-exercise-classes/' + chkArray.join(',');
      });
    }
  };
})(jQuery, Drupal);