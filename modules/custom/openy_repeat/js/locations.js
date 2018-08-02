(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_locations = {
    attach: function (context, settings) {

      $(window).once('openy-load-selected-locations').on('load', function() {
        $('.schedule-locations__item input', context).each(function () {
          if ($(this).is(':checked')) {
            $(this).parent('label').attr('class', 'selected');
          }
        });
        toggleSubmit(context);
      });

      // Toggle active class on location item.
      $('.schedule-locations__item input', context).once('openy-selected-locations').on('change', function() {
        if(!$(this).parent().hasClass('selected')) {
          $(this).parent('label').attr('class', 'selected');
        }
        else {
          $(this).parent().removeClass('selected');
        }
        toggleSubmit(context);
      });

      // Attach location arguments to url on submit.
      $('.js-submit-locations', context).once('openy-submit-locations').click(function() {
        var chkArray = [];
        $(".js-locations-row .js-location-box").each(function() {
          if ($(this).is(':checked')) {
            chkArray.push(this.value);
          }
        });
        // Get url from paragraph's field.
        var url = $('.field-prgf-repeat-lschedules-prf a').attr('href');
        location.href = url + '/?locations=' + chkArray.join(',');
      });

    }
  };

  // Toggle disable the submit button.
  var toggleSubmit = function(context) {
    if($('.schedule-locations__item label.selected').length > 0) {
      $('.js-submit-locations', context).removeClass('disabled');
    } else {
      $('.js-submit-locations', context).addClass('disabled');
    }
  };

})(jQuery, Drupal);