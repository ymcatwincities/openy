/**
 * @file
 * This file helps ajax-ify fontyourface admin section enable and disable links
 */
(function ($) {
  'use strict';
  Drupal.behaviors.blockContentDetailsSummaries = {
    attach: function (context) {
      $('.fontyourface-font-manager-item .font').each(function () {
        var font_status = $(this).find('.font-status');
        if ($(font_status).hasClass('enabled')) {
          $(this).removeClass('disabled');
          $(this).addClass('enabled');
          $(font_status).text(Drupal.t('Disable'));
        }
        if ($(font_status).hasClass('disabled')) {
          $(this).removeClass('enabled');
          $(this).addClass('disabled');
          $(font_status).text(Drupal.t('Enable'));
        }
      });
    }
  };
})(jQuery);
