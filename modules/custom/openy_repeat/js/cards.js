(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.repeat_cards = {
    attach: function (context, settings) {

      $(window).once('openy-load-selected-cards').on('load', function() {
        $('.openy-card__item input', context).each(function () {
          if ($(this).is(':checked')) {
            $(this).parent('label').attr('class', 'selected');
          }
        });
        toggleSubmit(context);
      });

      // Toggle active class on location item.
      $('.openy-card__item input', context).once('openy-selected-cards').on('change', function() {
        if(!$(this).parent().hasClass('selected')) {
          $(this).parent('label').attr('class', 'selected');
        }
        else {
          $(this).parent().removeClass('selected');
        }
        toggleSubmit(context);
      });

    }
  };

  // Toggle disable the submit button.
  var toggleSubmit = function(context) {
    if($('.openy-card__item label.selected').length > 0) {
      $('.js-submit-locations', context)
        .removeClass('disabled')
        .parent()
        .find('.error')
        .remove();
    } else {
      $('.js-submit-locations', context).addClass('disabled');
    }
  };

})(jQuery, Drupal);