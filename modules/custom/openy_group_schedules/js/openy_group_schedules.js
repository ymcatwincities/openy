(function($) {

  Drupal.openy_group_schedules = Drupal.openy_group_schedules || {};

  /**
   * Update "Date" select value to be equal with "filter_date" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_filter_date = function(parameters) {
    if (typeof(parameters.filter_date) !== 'undefined') {
      var split_date = parameters.filter_date.split('/');
      var day = split_date[0].length == 1 ? '0' + split_date[0] : split_date[0];
      var year = parseInt(split_date[2]) + 2000;
      var date =  year + '-' + day + '-' + split_date[1];
      $('input[name="date_select"]').val(date);
    }
  };

  /**
   * Update "Class" select value to be equal with "class" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_class_select = function(parameters) {
    if (typeof(parameters.class) !== 'undefined') {
      var exists = 0 !== $('#class-select-wrapper select option[value="' + parameters.class + '"]').length;
      if (exists) {
        $('#class-select-wrapper select').val(parameters.class);
      }
    }
  };


  /**
   * Update "Category" select value to be equal with "category" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_category_select = function(parameters) {
    if (typeof(parameters.category) !== 'undefined') {
      var exists = 0 !== $('#category-select-wrapper select option[value="' + parameters.category + '"]').length;
      if (exists) {
        $('#class-select-wrapper select').val(parameters.category);
      }
    }
  };

  /**
   * Update "Instructor" select value to be equal with "instructor" query param.
   *
   * @param parameters
   */
  Drupal.openy_group_schedules.update_instructor_select = function(parameters) {
    if (typeof(parameters.instructor) !== 'undefined') {
      var exists = 0 !== $('#instructor-select-wrapper select option[value="' + parameters.instructor + '"]').length;
      if (exists) {
        $('#instructor-select-wrapper select').val(parameters.instructor);
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
      $('#location-wrapper').addClass('hidden');
    }
    else if (typeof(parameters.view_mode) !== 'undefined' && parameters.view_mode == 'class') {
      $('#location-select-wrapper, #class-select-wrapper, #instructor-select-wrapper').removeClass('hidden');
      $('#location-wrapper').addClass('hidden');
    }
    else {
      $('#location-select-wrapper, #date-select-wrapper, #class-select-wrapper, #instructor-select-wrapper').removeClass('hidden');
      $('#location-wrapper').addClass('hidden');
    }

    Drupal.openy_group_schedules.update_class_select(parameters);
    Drupal.openy_group_schedules.update_instructor_select(parameters);
    Drupal.openy_group_schedules.update_filter_date(parameters);
    Drupal.openy_group_schedules.update_category_select(parameters);
    Drupal.openy_group_schedules.update_location_select(parameters);
  };

  /**
   * General handlers.
   *
   */
  Drupal.behaviors.openy_group_schedules = {
    attach: function (context, settings) {
      if (!$('#location-select-wrapper').hasClass('hidden') || !$('#date-select-wrapper').hasClass('hidden') || !$('#class-select-wrapper').hasClass('hidden') || !$('#category-select-wrapper').hasClass('hidden')) {
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
