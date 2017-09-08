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
          Drupal.behaviors.alert_dismiss.addDismissed(nid);
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
        expires: 7
      });
      $.cookie.json = jQueryCookieJson;
    },

    addDismissed: function (nid) {
      // Store dimsmissed alerts ids into cookie.
      var dismissed = Drupal.behaviors.alert_dismiss.getDismissed();
      if ($.inArray(nid, dismissed) == -1) {
        dismissed.push(nid);
      }
      Drupal.behaviors.alert_dismiss.setDismissed(dismissed);
    },

    isDismissed: function (nid) {
      var dismissed = Drupal.behaviors.alert_dismiss.getDismissed();
      if ($.inArray(nid, dismissed) === -1) {
        return false;
      }
      return true;
    }

  };

  Drupal.behaviors.alert_modal = {
    attach: function (context, settings) {
      $('.openy-alert-dialog', context).once('openy-alert-process-dialog').each(function () {
        var self = $(this);
        var nid = parseInt(self.data('nid'));
        if (!Drupal.behaviors.alert_dismiss.isDismissed(nid)) {
          // ToDo: pass head colors through settings.
          var dialog_classes = 'openy-alert-modal openy-alert-modal-head-yellow openy-alert-modal-' + nid;
          // Init dialog modal.
          // See jqueryui.com/dialog
          self.dialog({
            dialogClass: dialog_classes,
            autoOpen: false,
            modal: true,
            show: { effect: "blind", duration: 600, delay: 300 },
            hide: { effect: "drop", duration: 600, delay: 0 },
            width: 371,
          });
          // Open dialog modal.
          self.dialog('open');
          // We want to show it only once anyway.
          Drupal.behaviors.alert_dismiss.addDismissed(nid);
        }
      });

    }
  };

})(jQuery);
