(function ($) {
  "use strict";
  Drupal.openy_tour = Drupal.openy_tour || {};

  $(document).ajaxSuccess(function() {
    var queryString = decodeURI(window.location.search);
    if (/tour=?/i.test(queryString) || window.location.hash == '#tour=1') {
      var processed = true;
      $('.joyride-tip-guide').each(function() {
        if ($(this).css('display') == 'block' && processed) {
          $(this).find('.joyride-next-tip').trigger('click');
          processed = false;
        }
      });
    }
  });

  Drupal.behaviors.openy_tour = {
    attach: function (context, settings) {
      $('body').on('tourStart', function () {
        window.location.hash = 'tour=1';
        Drupal.openy_tour.click_button();
      });
      $('body').on('tourStop', function () {
        window.location.hash = '';
      });
    }
  };

  Drupal.openy_tour.click_button = function () {
    var $tipGuide;

    // Hide original next button if custom is appear and add focus.
    $('.joyride-next-tip').focus(function () {
      $tipGuide = $(this).parents('.joyride-tip-guide');
      if ($tipGuide.find('.openy-click-button').length > 0) {
        if ($tipGuide.find('.openy-click-button').attr('data-click-button') == 'false') {
          $tipGuide.find('.openy-click-button').show().focus();
          $tipGuide.find('.joyride-next-tip').hide();
        }
      }
    });

    $('.openy-click-button').on('click', function (e) {
      e.preventDefault();
      var selector = $(this).data('tour-selector'),
          element = {};
      // Click on link if class/id is provided.
      if ($(selector).length > 0) {
        element = $(selector);
      }
      // Click on input if data selector is provided.
      if ($('input[data-drupal-selector="' + selector + '"]').length > 0) {
        element = $('input[data-drupal-selector="' + selector + '"]');
      }
      element.parents('details').attr('open', true);
      element.trigger('click');
      $(this).attr('data-click-button', 'true');
      $(this).hide().parents('.joyride-content-wrapper').find('.joyride-next-tip').show().focus();
    });
  };

})(jQuery);
