(function ($) {
  "use strict";
  Drupal.openy_tour = Drupal.openy_tour || {};

  $(document).ajaxSuccess(function() {
    var queryString = decodeURI(window.location.search);
    if (/tour=?/i.test(queryString)) {
      var processed = true;
      $('.joyride-tip-guide').each(function() {
        if ($(this).css('display') == 'block' && processed) {
          console.log($(this).css('display'));
          $(this).find('.joyride-next-tip').trigger('click');
          processed = false;
        }
      });
    }
  });

  Drupal.behaviors.openy_tour = {
    attach: function (context, settings) {
      Drupal.openy_tour.click_button();
    }
  };

  Drupal.openy_tour.click_button = function () {
    $('.joyride-tip-guide').each(function() {
      // Hide original next button if custom is appear.
      if ($(this).find('.openy-click-button').length > 0) {
        $(this).find('.joyride-next-tip').hide();
      }
    });
    $('.openy-click-button').on('click', function (e) {
      e.preventDefault();
      var selector = $(this).data('tour-selector');
      // Click on link if class/id is provided.
      $(selector).trigger('click');
      // Click on input if data selector is provided.
      $('input[data-drupal-selector="' + selector + '"]').trigger('mousedown');
    });
  };

})(jQuery);
