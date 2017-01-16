(function ($) {
  "use strict";

  Drupal.behaviors.ymca_membership_tabs = {
    attach: function (context, settings) {
      $('.btn[href*="location-list"]').once().click(function (e) {
        $('.membership-summary').data('package-name', $(this).data('package-name'));
        $('.membership-continue').hide();
        $('a[href*="step2"]').click();
        $('html, body').animate({
          scrollTop: $('.h1').offset().top
        }, 300);
      });
      $('.field-membership-info .radio-block').once().click(function (e) {
        $('.membership-summary').data('branch-name', $(this).data('branch-name'));
        $('.membership-continue')
          .text($(this).find('a').text())
          .attr('href', $(this).find('a').attr('href'))
          .fadeIn();
      });
    }
  };
})(jQuery);

