(function($) {

  window.onpopstate = function(event) {
    window.location.reload();
  };

  $.fn.groupExLocationAjaxAction = function(parameters) {
    var params = [];
    for (var key in parameters) {
      if (key !== '_wrapper_format') {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.pushState(null, null, window.location.pathname + '?' + params.join('&'));

    if (typeof(parameters['instructor']) !== 'undefined' && window.location.pathname.match(/all_y_schedules/g)) {
      $('#location-select-wrapper, #date-select-wrapper, #location-wrapper').addClass('hidden');
    }
    else if (window.location.href.match(/all_y_schedules/g)) {
      // Form widgets hide/show.
      $('#location-select-wrapper, #date-select-wrapper').removeClass('hidden');
      $('#location-wrapper').addClass('hidden');
    }
  };

  Drupal.behaviors.ymca_groupex = {
    attach: function (context, settings) {
      if (window.location.search.match(/instructor/g)) {
        $('#location-select-wrapper, #date-select-wrapper, #location-wrapper').addClass('hidden');
      }
      $('.groupex-form-full input[type="radio"]').change(function() {
        $(this).parents('form').find('label').addClass('disabled');
      });

      $('.groupex-form-full select').change(function() {
        $('.groupex-form-full select').attr('disabled', true);
        $(document).ajaxSuccess(function() {
          $('.groupex-form-full select').removeAttr('disabled')
        });
      });
    }
  };

})(jQuery);
