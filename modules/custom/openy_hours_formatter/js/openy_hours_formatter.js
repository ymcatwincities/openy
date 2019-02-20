(function ($) {

  "use strict";
  Drupal.behaviors.openy_hours_formatter = {
    attach: function (context, settings) {
      $('.today-hours .show-link').once().on('click', function(e) {
        e.preventDefault();
        $(this)
          .addClass('hidden')
          .parent()
          .find('.hide-link').removeClass('hidden')
          .parent()
          .find('.branch-hours').removeClass('hidden');
      });
      $('.today-hours .hide-link').once().on('click', function(e) {
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
     * See https://moment.github.io/luxon/docs/manual/formatting.
     * @returns {string}
     */
    getDayOfWeek(tz) {
      return luxon.DateTime.local().setZone(tz).toFormat('c');
    },

    /**
     * See https://moment.github.io/luxon/docs/manual/formatting.
     * @returns {string}
     */
    getDate(tz) {
      return luxon.DateTime.local().setZone(tz).toFormat('yyyy-LL-dd');
    },

    /**
     * Primary method for updating the today hours.
     */
    updateTodayHours() {
      var $todayHours = $('.today-hours > .today');
      var hoursData = [];
      var branchHours = drupalSettings.openy_hours_formatter.branch_hours || [];
      var tz = drupalSettings.openy_hours_formatter.tz;

      // Prioritize these arbitrary hours names first.
      ['branch_hours', 'center_hours', 'open_hours', 'before_school_enrichment'].reverse().forEach(function(name) {
        if (typeof branchHours[name] != 'undefined') {
          hoursData = branchHours[name];
        }
      });
      // Fallback to the first set of hours then.
      if (!hoursData.length && branchHours.length) {
        hoursData = branchHours.slice(0, 1);
      }

      if (hoursData.length) {
        var todayString = this.getDate(tz);
        var dayOfWeek = this.getDayOfWeek(tz);
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

        // RUn for the first time
        this.updateTodayHours();
        $todayHours.addClass(onceClass);
      }
    }

  };
})(jQuery);
