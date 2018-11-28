/**
 * @file
 * Countdown to the end registration date for 'Register' block.
 */
(function ($, Drupal) {
  Drupal.behaviors.campaignCountdown = {
    attach: function (context, settings) {
      if (Drupal.behaviors.campaignCountdown.length){
        return;
      }
      $('.countdown').html('');

      // Parse campaign end registration date to the Date object.
      var dateObj = moment(settings.campaignSettings.endRegDate + '.0000Z');
      var campaignRegEndDate = new Date(dateObj);

      simplyCountdown('.countdown', {
        year: campaignRegEndDate.getFullYear(),
        month: campaignRegEndDate.getMonth() + 1,
        day: campaignRegEndDate.getDate(),
        hours: campaignRegEndDate.getHours(),
        minutes: campaignRegEndDate.getMinutes(),
        seconds: campaignRegEndDate.getSeconds(),
        words: {
          days: 'day',
          hours: 'hour',
          minutes: 'minute',
          seconds: 'second',
          pluralLetter: 's'
        },
        plural: true,
        inline: false,
        inlineClass: 'simply-countdown-inline',
        enableUtc: false,
        onEnd: function () {
          return;
        },
        refresh: 1000,
        sectionClass: 'simply-section',
        amountClass: 'simply-amount',
        wordClass: 'simply-word'
      });
    }
  };
})(jQuery, Drupal);
