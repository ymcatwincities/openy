/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * Scroll to next button.
   */
  Drupal.behaviors.scrollToNext = {
    attach: function (context, settings) {
      $(context).find('.calc-block-form').once('calcForm').each(function () {
        $(this).find('.btn-lg.btn').on('click', function () {

          $(context).find('.form-radios .btn-lg.btn').removeClass('btn-success');
          $(this).addClass('btn-success');

          $('html, body').animate({
            scrollTop: $(".form-submit").offset().top
          }, 2000);
        });
      });
    }
  };

  /**
   * Views scroll to top ajax command override.
   */
  Drupal.behaviors.scrollOffset = {
    attach: function (context, settings) {
      if (typeof Drupal.AjaxCommands === 'undefined') {
        return;
      }
      Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
        // Scroll to the top of the view. This will allow users
        // to browse newly loaded content after e.g. clicking a pager
        // link.
        var offset = $(response.selector).offset();
        // We can't guarantee that the scrollable object should be
        // the body, as the view could be embedded in something
        // more complex such as a modal popup. Recurse up the DOM
        // and scroll the first element that has a non-zero top.
        var scrollTarget = response.selector;
        while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
          scrollTarget = $(scrollTarget).parent();
        }
        // Only scroll upward.
        if (offset.top - 10 < $(scrollTarget).scrollTop()) {
          $(scrollTarget).animate({scrollTop: (offset.top - 230)}, 500);
        }
      };
    }
  };
})(jQuery);
