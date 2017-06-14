(function($) {

  Drupal.openy_group_schedules = Drupal.openy_group_schedules || {};

  /**
   * Update "Date" select value to be equal with "filter_date" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_filter_date = function(parameters) {
    if (typeof(parameters.filter_date) !== 'undefined') {
      var date = parameters.filter_date.replace('0','');
      if (date.charAt(0) === '0') {
        date = date.slice(1);
      }
      var exists = 0 !== $('select[name="date_select"] option[value="' + date + '"]').length;
      if (exists) {
        $('select[name="date_select"]').val(date);
      }
    }
  };

  /**
   * Update "Class" select value to be equal with "class" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_class_select = function(parameters) {
    if (typeof(parameters.view_mode) !== 'undefined' && parameters.view_mode == 'class') {
      $('#date-select-wrapper, #location-wrapper').addClass('hidden');
      $('#class-select-wrapper, #location-select-wrapper').removeClass('hidden');
    }
    if (typeof(parameters.class) !== 'undefined') {
      var exists = 0 !== $('#class-select-wrapper select option[value="' + parameters.class + '"]').length;
      if (exists) {
        $('#class-select-wrapper select').val(parameters.class);
      }
    }
  };

  /**
   * Update "Location" select value to be equal with "location" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_location_select = function(parameters) {
    if (typeof(parameters.location) !== 'undefined') {
      var exists = 0 !== $('#location-select-wrapper select option[value="' + parameters.location + '"]').length;
      if (exists) {
        $('#location-select-wrapper select').val(parameters.location);
      }
    }
  };

  /**
   * Reload page on browser's back or forward buttons.
   */
  window.onpopstate = function(event) {
    window.location.reload();
  };

  /**
   * Triggered by AJAX action for updating browser URL query options and browser's history.
   *
   * @param parameters
   */
  $.fn.groupExLocationAjaxAction = function(parameters) {
    var params = [];
    for (var key in parameters) {
      if (key !== '_wrapper_format') {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.pushState(null, null, window.location.pathname + '?' + params.join('&'));

    if (typeof(parameters.instructor) !== 'undefined') {
      $('#date-select-wrapper, #location-wrapper, #class-select-wrapper').addClass('hidden');
    }
    else if (typeof(parameters.view_mode) !== 'undefined' && parameters.view_mode == 'class') {
      $('#location-select-wrapper, #class-select-wrapper').removeClass('hidden');
      $('#date-select-wrapper, #location-wrapper').addClass('hidden');
    }
    else {
      $('#location-select-wrapper, #date-select-wrapper').removeClass('hidden');
      $('#class-select-wrapper, #location-wrapper').addClass('hidden');
    }

    Drupal.openy_group_schedules.update_class_select(parameters);
    Drupal.openy_group_schedules.update_filter_date(parameters);
    Drupal.openy_group_schedules.update_location_select(parameters);
  };

  /**
   * General handlers.
   *
   */
  Drupal.behaviors.openy_group_schedules = {
    attach: function (context, settings) {
      if (!$('#location-select-wrapper').hasClass('hidden') || !$('#date-select-wrapper').hasClass('hidden') || !$('#class-select-wrapper').hasClass('hidden')) {
        $('.groupex-form-full .top-form-wrapper').removeClass('hidden');
      }

      $('.groupex-form-full input[type="radio"]').change(function() {
        $(this).parents('form').find('label').addClass('disabled');
      });
      $(document).ajaxSuccess(function() {
        if (typeof addtocalendar !== 'undefined') {
          addtocalendar.load();
        }
      });
      $('.groupex-form-full select').change(function() {
        $('.groupex-form-full select').attr('readonly', true);
        $('div.groupex-results').hide();

        $(document).ajaxSuccess(function() {
          if (typeof addtocalendar !== 'undefined') {
            addtocalendar.load();
          }
          $('div.groupex-results').show();
          $('.groupex-form-full select').removeAttr('readonly');
        });
      });
    }
  };

})(jQuery);
