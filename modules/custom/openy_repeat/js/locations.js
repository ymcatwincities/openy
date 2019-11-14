(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_locations = {
    attach: function (context, settings) {

      $(window).once('openy-load-selected-locations').on('load', function() {
        $('.openy-card__item input', context).each(function () {
          if ($(this).is(':checked')) {
            $(this).parents('.openy-card__item').addClass('selected');
          }
        });
        toggleSubmit(context);
      });

      // Toggle active class on location item.
      $('.openy-card__item input', context).once('openy-selected-locations').on('change', function() {
        var locName = this.value;
        if(!$(this).parents('.openy-card__item').hasClass('selected')) {
          $(this).parents('.openy-card__item').addClass('selected');
          $('#selected-locations').append('<li>' + locName + '</li>');
        }
        else {
          $(this).parents('.openy-card__item').removeClass('selected');
          $('#selected-locations li:contains("'+ locName +'")').remove();
        }
        toggleSubmit(context);
      });

      // Hide scroll button.
      $('body').find('.return-to-top').addClass('hidden');

      // Toggle to result page.
      $('.skip').click(function () {
        // Get url from paragraph's field.
        var url = $('.field-prgf-repeat-lschedules-prf a').attr('href');
        location.href = url;
        return false;
      });

      // Attach location arguments to url on submit.
      $('.js-submit-locations', context).once('openy-submit-locations').click(function() {
        if ($(this).hasClass('disabled')) {
          if ($(this).parent().find('.error').length === 0) {
            $(this).after('<div class="error">' + Drupal.t('Please choose the location') + '</div>');
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

  // Toggle disable the submit button.
  var toggleSubmit = function(context) {
    if($('.openy-card__item.selected label').length > 0) {
      $('.js-submit-locations', context)
        .removeClass('next-hidden')
        .addClass('next-view')
        .parent()
        .find('.error')
        .remove();
      $('.location-select', context).addClass("hidden");
      $('.d-flex-location', context).removeClass("hidden");
      $('.locations-footer')
        .removeClass("footer-custom-hidden")
        .addClass('footer-custom-show');
      $('.skip', context).addClass('skip-hidden');
    } else {
      $('.js-submit-locations', context)
        .addClass('next-hidden')
        .removeClass('next-view');
      $('.location-select').removeClass("hidden");
      $('.d-flex-location', context).addClass("hidden");
      $('.locations-footer')
        .addClass("footer-custom-hidden")
        .removeClass('footer-custom-show');
      $('.skip', context).removeClass('skip-hidden');
    }
  };

})(jQuery, Drupal);