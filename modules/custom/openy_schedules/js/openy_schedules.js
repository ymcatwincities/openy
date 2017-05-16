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
      if ((key === 'location' ||
        key === 'program' ||
        key === 'category' ||
        key === 'class' ||
        key === 'date' ||
        key === 'time') &&
        parameters[key] !== 'all') {
        params.push(key + '=' + parameters[key]);
      }
    }
    history.replaceState({}, '', window.location.pathname + '?' + params.join('&'));
  };

  Drupal.behaviors.openy_schedules = {
    attach: function(context, settings) {
      $('.openy-schedules-search-form .js-form-item-date input').datepicker({
        onSelect: function(dateText, ins) {
          $(this)
            .parents('.openy-schedules-search-form')
            .each(function() {
              var form = $(this);
              form.find('.js-form-submit').trigger('click');
              form.find('.js-form-type-select select').attr('readonly', true);
            });
        }
      });

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data !== undefined && settings.data.match('form_id=openy_schedules_search_form')) {
            var form = $('.openy-schedules-search-form');
            form.find('.js-form-type-select select').removeAttr('readonly');
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
            prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>',
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
})(jQuery);
