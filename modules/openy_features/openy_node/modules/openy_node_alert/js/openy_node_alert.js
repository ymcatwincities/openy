(function ($) {
  "use strict";
  Drupal.behaviors.build_counter = {
    attach: function (context, settings) {
      $('.header-alerts-list, .footer-alerts-list', context).once('header-alert-list-arrows').each(function () {
        $('.slick__arrow', this).wrap('<div class="container"></div>');
        if ($(this).find('.slick__counter').length === 0) {
          var current = $(this).find('.slick-active button').text(),
            total = $(this).find('.slick-dots li:last').text();
          $('<div class="slick__counter">' + Drupal.t('<span class="current">@current</span> of <span class="total">@total</span>', {
              '@current': current,
              '@total': total
            }) +
            '</div>').insertBefore($(this).find('.slick__arrow'));
        }
        $(this).on('afterChange', function (event, slick, direction) {
          var current = $(this).find('.slick-active button').text(),
            total = $(this).find('.slick-dots li:last').text();
          $(this).find('.slick__counter .current').text(current);
          $(this).find('.slick__counter .total').text(total);
        });
      });
    }
  };

  Drupal.behaviors.alert_dismiss = {
    attach: function (context, settings) {
      var dismissed = Drupal.behaviors.alert_dismiss.getDismissed();
      $('.site-alert', context).once('alert-dismiss').each(function () {
        var self = $(this);
        var nid = parseInt(self.data('nid'));
        var slick = self.parents('.slick__slider').first();
        // Remove dismissed alerts.
        if ($.inArray(nid, dismissed) != -1) {
          var index = self.parents('.slick__slide').eq(0).index();
          if (slick.length > 0 && index > 0) {
            var slickCheck = slick.slick('slickRemove', index -1);
            if(!slickCheck) {
              self.remove();
              slick.parents('.slick-track').prevObject.remove();
            }
          }
          else {
            self.remove();
          }
        }
        $('.site-alert__dismiss', self).on('click', function () {
          if (slick.length > 0) {
            var slickCheck = slick.slick('slickRemove', self.parents('.slick__slide').eq(0).index()-1);
            if(!slickCheck) {
              self.remove();
              slick.parents('.slick-track').prevObject.remove();
              $('.slick-arrow').remove();
            }
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
      $.cookie('alerts_dismiss', dismissed, {
        expires: 7,
        path: '/'
      });
      $.cookie.json = jQueryCookieJson;
    }
  };

  Drupal.behaviors.removeUnneededAria = {
    attach: function (context, settings) {
      $('.slick-list', context).once().each(function () {
        $(this).removeAttr('aria-live');
      });
      $('.slick-track', context).once().each(function () {
        $(this).removeAttr('role');
      });
      $('.slick__slide', context).once().each(function () {
        $(this).removeAttr('aria-describedby');
        $(this).removeAttr('role');
      });
    }
  };

  /**
   * Match Height on alerts.
   */
  Drupal.behaviors.alertsHeight = {
    attach: function (context, settings) {
      setTimeout(function () {
        $('[class^="alert"]', context).matchHeight();
      }, 1000);
    }
  };

})(jQuery);
