(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_locations = {
    attach: function (context, settings) {

      // Attach location arguments to url on submit.
      $('.js-submit-locations', context).once('openy-submit-locations').click(function() {
        if ($(this).hasClass('disabled')) {
          if ($(this).parent().find('.error').length === 0) {
            $(this).before('<div class="error">' + Drupal.t('Please choose the location') + '</div>');
          }
          return false;
        }
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

})(jQuery, Drupal);