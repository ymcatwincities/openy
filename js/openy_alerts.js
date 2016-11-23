(function ($) {
  "use strict";

  Drupal.behaviors.alert_dismiss = {
    attach: function (context, settings) {
      $('.header-alerts-list', context).once('header-alert-list-arrows').each(function () {
        $('.slick__arrow', this).wrap('<div class="container"></div>');
      });

      var dismissed = Drupal.behaviors.alert_dismiss.getDismissed();
      $('.site-alert', context).once('alert-dismiss').each(function () {
        var self = $(this);
        var nid = parseInt(self.data('nid'));
        var slick = self.parents('.slick__slider').first();
        // Remove dismissed alerts.
        if ($.inArray(nid, dismissed) != -1) {
          if (slick.size() > 0) {
            slick.slick('slickRemove', self.parents('.slick__slide').eq(0).index());
          }
          else {
            self.remove();
          }
        }
        $('.site-alert__dismiss', self).on('click', function () {
          if (slick.size() > 0) {
            slick.slick('slickRemove', self.parents('.slick__slide').eq(0).index());
          }
          else {
            self.remove();
          }
          // Store dimsmissed alerts ids into cookie.
          dismissed = Drupal.behaviors.alert_dismiss.getDismissed();
          if ($.inArray(nid, dismissed) == -1) {
            dismissed.push(nid);
          }
          Drupal.behaviors.alert_dismiss.setDismissed(dismissed);
          return false;
        });
      });
      $('.header-alerts-list').addClass('header-alerts-list-processed');
    },

    getDismissed: function () {
      var jQueryCookieJson = $.cookie.json;
      $.cookie.json = true;
      var dismissed = $.cookie('alerts_dismiss') || [];
      $.cookie.json = jQueryCookieJson;
      return dismissed;
    },

    setDismissed: function (dismissed) {
      var jQueryCookieJson = $.cookie.json;
      $.cookie.json = true;
      $.cookie('alerts_dismiss', dismissed, {expires: 7});
      $.cookie.json = jQueryCookieJson;
    }
  };

})(jQuery);
