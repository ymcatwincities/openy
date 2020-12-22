(function ($) {

  "use strict";
  Drupal.behaviors.openy_hours_formatter = {
    attach: function (context, settings) {
      $('.today-hours .show-link').once().on('click', function (e) {
        e.preventDefault();
        $(this)
          .addClass('hidden')
          .parent()
          .find('.hide-link').removeClass('hidden')
          .parent()
          .find('.branch-hours').removeClass('hidden');
      });
      $('.today-hours .hide-link').once().on('click', function (e) {
        e.preventDefault();
        $(this)
          .addClass('hidden')
          .parent()
          .find('.show-link').removeClass('hidden')
          .parent()
          .find('.branch-hours').addClass('hidden');
      });
    }
  };

  Drupal.behaviors.today_hours = {

    /**
     * Used to track the setInveral.
     */
    refreshTimer: false,

    /**
     * @returns {string}
     */
    getDayOfWeek: function (tz) {
      return moment().tz(tz).format('d');
    },

    /**
     * @returns {string}
     */
    getDate: function (tz) {
      return moment().tz(tz).format('YYYY-MM-DD');
    },

    /**
     * Primary method for updating the today hours.
     */
    updateTodayHours: function () {
      if (typeof drupalSettings.openy_hours_formatter === 'undefined') {
        drupalSettings.openy_hours_formatter = {};
      }

      var $todayHours = $('.today-hours > .today');
      var hoursData = [];
      var branchHours = drupalSettings.openy_hours_formatter.branch_hours || [];
      var tz = drupalSettings.openy_hours_formatter.tz || 'America/New York';
      tz = tz.replace(/ /g,"_");
      // Prioritize these arbitrary hours names first.
      ['branch_hours', 'center_hours', 'open_hours', 'before_school_enrichment'].reverse().forEach(function (name) {
        if (typeof branchHours[name] != 'undefined') {
          hoursData = branchHours[name];
        }
      });
      // Fallback to the first set of hours then.
      if (!hoursData.length && branchHours.length) {
        hoursData = branchHours.slice(0, 1);
      }

      if (hoursData.length) {
        var todayString = Drupal.behaviors.today_hours.getDate(tz);
        var dayOfWeek = Drupal.behaviors.today_hours.getDayOfWeek(tz);
        dayOfWeek--;
        var hours = hoursData;
        var exceptions = []; // Holidays and other day exceptions will come later.

        if (typeof exceptions[todayString] != 'undefined') {
          $todayHours.text(exceptions[todayString]);
        }
        else {
          $todayHours.text(hours[dayOfWeek]);
        }
      }
    },

    /**
     * Drupal behavior attach.
     *
     * @param context
     * @param settings
     */
    attach: function (context, settings) {

      var $todayHours = $('.today-hours > .today');
      var onceClass = 'refresh-interval-set';

      // Bail out if there's already refresh action set.
      if (!$todayHours.hasClass(onceClass)) {

        // This will ensure that if people leave the tab open or the page comes back
        // into memory on a phone the hour will always be correct.
        this.refreshTimer = setInterval(this.updateTodayHours, 60 * 1000);

        // Run for the first time.
        this.updateTodayHours();
        $todayHours.addClass(onceClass);
      }
    }

  };
})(jQuery);
