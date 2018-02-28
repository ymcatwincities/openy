(function($) {
  "use strict";

  /**
   * Triggered by AJAX action for updating browser URL query options and browser's history.
   *
   * @param parameters
   */
  $.fn.schedulesAjaxAction = function(parameters) {
    var params = [];
    for (var key in parameters) {
      // Skip some form keys.
      if (
        parameters[key].length >= 1 &&
        (
          key === 'location' ||
          (
            parameters[key] !== 'all' &&
            (
              key === 'program' ||
              key === 'category' ||
              key === 'class' ||
              key === 'date' ||
              key === 'time'
            )
          )
        )
      ) {
        params.push(key + '=' + parameters[key]);
      }
      // Handle the display.
      if (key === 'display' && parameters[key] !== 0 && parameters[key] !== null) {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.replaceState({}, '', window.location.pathname + '?' + params.join('&'));
  };

  /**
   * Break down query string and build an array.
   *
   * @param url string
   *   String url, with possible parameters.
   *
   * @returns {Array}
   */
  function getUrlParams(url) {
    var query_array = [], hash, params = [], value = '';
    var link_url_split = url.split('?');
    var param_string = link_url_split[1];
    if(param_string !== undefined){
      params = param_string.split('&');
      for(var i = 0; i < params.length; i++){
        hash = params[i].split('=');
        value = decodeURI(hash[1]);
        query_array[decodeURI(hash[0])] = value;
      }
    }

    return query_array;
  }

  Drupal.behaviors.openy_schedules = {
    attach: function(context, settings) {
      // Makes all select elements read-only when the Week View checkbox state is changed.
      var form = $('.openy-schedules-search-form');
      form.find('.js-form-item-display input')
        .on('change', function() {
          form.find('.js-form-type-select select').attr('readonly', true);
        });

      form.find(':input.openy-schedule-datepicker').datepicker({
        onClose: function(dateText, inst) {
          // Make all form elements readonly. AJAX will remove this attribute.
          form.find(':input').attr('readonly', true);
        }
      });

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data !== undefined && settings.data.match('form_id=openy_schedules_search_form')) {
            var form = $('.openy-schedules-search-form');
            form.find('.js-form-type-select select').removeAttr('readonly');
            form.find(':input.openy-schedule-datepicker').removeAttr('readonly');
            form.find('.filters-container').addClass('hidden');
            if (form.find('.filter').length !== 0) {
              form.find('.filters-container').removeClass('hidden');
            }
            form.find('.add-filters').removeClass('hidden');
            form.find('.close-filters').addClass('hidden');
            form.find('.selects-container').addClass('hidden-xs');
          }
        });

      $('.schedule-sessions-group-slider').each(function() {
        var view = $(this);

        // Initialize Slick.
        if (!view.hasClass('slick-initialized')) {
          view.slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 3,
            prevArrow: '<button type="button" class="slick-prev" value="' + Drupal.t('Previous') + '" title="' + Drupal.t('Previous') + '">' + Drupal.t('Previous') + '<i class="fa fa-chevron-left" aria-hidden="true"></i></button>',
            nextArrow: '<button type="button" class="slick-next" value="' + Drupal.t('Next') + '" title="' + Drupal.t('Next') + '">' + Drupal.t('Next') + '<i class="fa fa-chevron-right" aria-hidden="true"></i></button>',
            customPaging: function(slider, i) {
              return '<button type="button" data-role="none" aria-hidden="true" role="button" tabindex="' + i + '" value="' + Drupal.t('Slide set @i', {'@i': i+1}) + '" title="' + Drupal.t('Slide set @i', {'@i': i+1}) + '">' + (i+1) + '</button>';
            },
            responsive: [{
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                arrows: true,
                dots: true
              }
            }, {
              breakpoint: 480,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                arrows: true,
                dots: true
              }
            }, {
              breakpoint: 768,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                arrows: true,
                dots: true
              }
            }]
          });
        }
      });

      $('.openy-schedules-search-form').each(function() {
        var form = $(this);
        // Filters actions.
        form.find('.add-filters')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.selects-container').removeClass('hidden-xs');
            form.find('.close-filters').removeClass('hidden');
            form.find('.filters-container').addClass('hidden');
            $(this).addClass('hidden');
          });
        form.find('.close-filters')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.selects-container').addClass('hidden-xs');
            form.find('.add-filters').removeClass('hidden');
            form.find('.filters-container').removeClass('hidden');
            $(this).addClass('hidden');
          });

        form.find('.js-form-type-select select')
          .on('change', function() {
            form.find('.js-form-type-select select').attr('readonly', true);
          });

        form.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            form.parents('.filter').remove();
            form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (form.find('.filter').length === 0) {
              form.find('.filters-main-wrapper').addClass('hidden');
            }
            form.find('.js-form-type-select select').attr('readonly', true);
            form.find('.js-form-submit').trigger('click');
          });

        form.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            form.find('.filters-main-wrapper').find('a.remove').each(function() {
              form.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            form.find('.js-form-type-select select').attr('readonly', true);
            form.find('.js-form-submit').trigger('click');
          });

        // Handle preferred location.
        setTimeout(function() {
          var preferred_branch = $.cookie('openy_preferred_branch');
          var query = getUrlParams(window.location.href);
          var location = query['location'];
          if (typeof location === 'undefined' && typeof preferred_branch !== 'undefined') {
            form.find('.js-form-item-location select')
              .val(preferred_branch)
              .trigger('change');
          }
        }, 0);
      });

      $('.schedule-sessions-group').once().each(function() {
        var group = $(this);

        var form = $('.openy-schedules-search-form'),
          input = form.find('input[name="date"]');

        var filter_date_string = input.val().split('/'),
          filter_date = new Date(filter_date_string[2], filter_date_string[0] - 1, filter_date_string[1]),
          today = new Date();
          today.setHours(0,0,0,0);
        if (today >= filter_date) {
          group.find('.prev-week').addClass('hidden');
        }
        else {
          group.find('.prev-week').removeClass('hidden');
        }
        group.find('.week-control').on('click', function(e) {
          e.preventDefault();
          if (!$(this).hasClass('week-control-processing')) {
            var current_date = input.val().split('/'),
              date = new Date(current_date[2], current_date[0] - 1, current_date[1]);

            if ($(this).hasClass('prev-week')) {
              date.setDate(date.getDate() - 7);
            }
            if ($(this).hasClass('next-week')) {
              date.setDate(date.getDate() + 7);
            }

            var new_date = date.format('mm/dd/yyyy');
            input.val(new_date);
            form.find('.js-form-type-select select').attr('readonly', true);
            form.find('.js-form-submit').trigger('click');
            $(this).addClass('week-control-processing');

            var filter_date_string = input.val().split('/'),
              filter_date = new Date(filter_date_string[2], filter_date_string[0] - 1, filter_date_string[1]),
              today = new Date();
              today.setHours(0,0,0,0);
            if (today >= filter_date) {
              group.find('.prev-week').addClass('hidden');
            }
            else {
              group.find('.prev-week').removeClass('hidden');
            }
          }
        });
      });
    }
  };

  Drupal.behaviors.openy_schedules_removeUnneededAria = {
    attach: function (context, settings) {
      $('.schedule-sessions-group-slider .slick-slide', context).once().each(function () {
        $(this).removeAttr('role');
      });
    }
  };

})(jQuery);
