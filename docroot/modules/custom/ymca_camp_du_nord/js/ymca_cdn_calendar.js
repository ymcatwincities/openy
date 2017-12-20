(function($) {

  function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
      results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

  Drupal.cdn = Drupal.cdn || {};

  Drupal.cdn.check_borders_mobile = function(cell) {
    if (cell.parents('.cdn-calendar-list').find('a.cdn-prs-product-mobile.selected').length > 1) {
      cell.parents('.cdn-calendar-list').find('.tip').remove();
      cell.parents('.cdn-calendar-list').find('a.last-selected').removeClass('last-selected');
      var last = cell.parents('.cdn-calendar-list').find('a.cdn-prs-product-mobile.selected:last');
      if (last.find('.tip').length === 0) {
        last.append('<div class="tip"><span>' + Drupal.t('$0 Check Out Date') + '</span></div>');
        last.addClass('last-selected');
      }
    }
  };

  Drupal.cdn.check_borders = function(cell) {
    cell.parents('.fc-view').find('a').removeClass('no-right-border').removeClass('no-left-border').removeClass('first-selected').removeClass('last-selected');
    if (cell.parents('.fc-view').find('a.selected').length > 1) {
      cell.parents('.fc-view').find('a.selected').addClass('no-right-border').addClass('no-left-border');
      cell.parents('.fc-view').find('a.selected:first').addClass('no-right-border first-selected').removeClass('no-left-border');
      var last = cell.parents('.fc-view').find('a.selected:last');
      last.addClass('no-left-border last-selected').removeClass('no-right-border');
      if (last.find('.fc-content .tip').length === 0) {
        last.find('.fc-content').append('<div class="tip"><span>' + Drupal.t('$0 Check Out Date') + '</span></div>');
        $('<div class="tip-q">$0<span class="q-mark"><i class="fa fa-question-circle"></i></span></div>').insertAfter(last.find('.fc-title'));
      }
    }
  };

  Drupal.cdn.validate_range = function(cell) {
    var products = cell.parents('.fc-view').find('a.cdn-prs-product'),
        first_selected = cell.parents('.fc-view').find('a.selected:first').find('.fc-day-number').text() * 1,
        last_selected = cell.parents('.fc-view').find('a.selected:last').find('.fc-day-number').text() * 1;
    // Select all dates we have to join.
    var list = [];
    for (var i = first_selected; i <= last_selected; i++) {
      list.push(i);
    }
    // Go through each product end ensure range is not splitted.
    products.each(function() {
      var day_number = $(this).find('.fc-day-number').text() * 1;
      if (list.length > 1 && $.inArray(day_number, list) !== -1 && !$(this).hasClass('selected')) {
        $(this).addClass('selected');
      }
    });
  };

  Drupal.cdn.validate_range_mobile = function(cell) {
    var products = cell.parents('.cdn-calendar-list').find('a.cdn-prs-product-mobile'),
      first_selected = cell.parents('.cdn-calendar-list').find('a.selected:first').find('.number').text() * 1,
      last_selected = cell.parents('.cdn-calendar-list').find('a.selected:last').find('.number').text() * 1;
    // Select all dates we have to join.
    var list = [];
    for (var i = first_selected; i <= last_selected; i++) {
      list.push(i);
    }
    // Go through each product end ensure range is not splitted.
    products.each(function() {
      var day_number = $(this).find('.number').text() * 1;
      if (list.length > 1 && $.inArray(day_number, list) !== -1 && !$(this).hasClass('selected')) {
        $(this).addClass('selected');
      }
    });
  };

  Drupal.cdn.update_total_mobile = function(link, settings) {
    var calendar = link.parents('.cdn-calendar'),
      index = calendar.data('index'),
      footer = $('.cdn-village-footer-bar[data-index="' + index + '"]'),
      price = 0,
      nights = 0,
      ids = [];
    calendar.find('.cdn-prs-product-mobile.selected').each(function() {
      if (!$(this).hasClass('last-selected')) {
        var pid = $(this).parent().data('pid');
        ids.push(pid);
        price += $(this).find('.price').text().replace('$', '') * 1;
        nights++;
      }
    });
    var href = settings.path.baseUrl + 'cdn/personify/login?ids=' + ids.join('%2C') + '&total=' + price + '&nights=' + nights;
    footer.find('.next-step').attr('href', href);
    footer.find('.price').text('$' + price);
    footer.find('.nights').text(nights);
    footer.addClass('active');
    if (nights === 0) {
      footer.addClass('not-active').removeClass('active');
    }
    // To do: update related desktop calendar.
  };

  Drupal.cdn.update_total = function(link, settings) {
    var calendar = link.parents('.cdn-calendar'),
        index = calendar.data('index'),
        footer = $('.cdn-village-footer-bar[data-index="' + index + '"]'),
        price = 0,
        nights = 0,
        ids = [];
    calendar.find('.cdn-prs-product.selected').each(function() {
      if (!$(this).hasClass('last-selected')) {
        var id = $(this).attr('href').split('/').pop(),
          pid = $('.cdn-calendar-list-row[data-id="' + id + '"]').data('pid');
        ids.push(pid);
        price += $(this).find('.fc-title').text().replace('$', '') * 1;
        nights++;
      }
    });
    var href = settings.path.baseUrl + 'cdn/personify/login?ids=' + ids.join('%2C') + '&total=' + price + '&nights=' + nights;
    footer.find('.next-step').attr('href', href);
    footer.find('.price').text('$' + price);
    footer.find('.nights').text(nights);
    footer.addClass('active');
    if (nights === 0) {
      footer.addClass('not-active').removeClass('active');
    }
    // To do: update related mobile view calendar.
  };

  /**
   * Parse Drupal events from the DOM.
   */
  Drupal.cdn.parseEvents = function (details) {
    var events = [];
    for (var i = 0; i < details.length; i++) {
      var event = $(details[i]);
      events.push({
        field: event.data('field'),
        index: event.data('index'),
        eid: event.data('eid'),
        entity_type: event.data('entity-type'),
        title: event.attr('title'),
        start: event.data('start'),
        end: event.data('end'),
        url: event.attr('href'),
        allDay: (event.data('all-day') === 1),
        className: event.data('cn'),
        editable: (event.data('editable') === 1),
        dom_id: this.dom_id
      });
    }
   return events;
  };

  // Fullcalendar handlers work only in global scope.
  $('.fullcalendar').each(function() {
    $(this).fullCalendar({
      defaultView: 'week',
      views: {
        week: {
          type: 'basicWeek',
          duration: { weeks: 6 }
        }
      }
    });
    var events = Drupal.cdn.parseEvents($(this).parents('.cdn-calendar').find('.fullcalendar-event-details'));
    $(this).fullCalendar('renderEvents', events, true);
  });

  Drupal.behaviors.cdn = {
    attach: function(context, settings) {
      $('.fullcalendar', context).each(function() {
        // Change month based on start date.
        var date = $.fullCalendar.moment($('input[name="arrival_date"]').val());
        $(this).fullCalendar('gotoDate', date);
        $(this).find('.fc-day-top').each(function() {
          var number = $(this).find('.fc-day-number').text();
          var i = $(this).index();
          $(this)
            .attr('data-index', i);
          if ($(this).parents('table:eq(0)').find('tbody td:eq(' + i + ') .fc-day-number').length === 0) {

            if ($(this).parents('table:eq(0)').find('tbody td:eq(' + i + ') .fc-day-grid-event').length === 1) {
              var date = $(this).data('date');
              $(this)
                .hide()
                .parents('table:eq(0)')
                .find('tbody td:eq(' + i + ') .fc-day-grid-event')
                .addClass(date)
                .find('.fc-content')
                .prepend('<div class="fc-day-number">' + number + '</div>');
            } else {
              $(this)
                .hide()
                .parents('table:eq(0)')
                .find('tbody td:eq(' + i + ')')
                .prepend('<div class="fc-day-number">' + number + '</div>');
            }
          }
        });
      });

      // Handles mobile view dates selection functionality.
      $('a.cdn-prs-product-mobile', context).each(function() {
        $(this).on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          if ($(this).hasClass('booked')) {
            return;
          }
          if (!$(this).hasClass('selected')) {
            $(this).addClass('selected');
          } else {
            $(this).removeClass('selected');
          }
          Drupal.cdn.check_borders_mobile($(this));
          Drupal.cdn.validate_range_mobile($(this));
          Drupal.cdn.update_total_mobile($(this), settings);
        });
      });

      // Handles desktop view dates selection functionality.
      $('a.cdn-prs-product', context).each(function() {
        // Check if date is booked.
        var id = $(this).attr('href').replace(settings.path.baseUrl + 'admin/structure/cdn_prs_product/', '');
        if ($('.cdn-calendar-list-row[data-id="' + id + '"]').hasClass('booked')) {
          $(this).addClass('booked');
        }
        $(this).on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          if ($(this).hasClass('booked')) {
            return;
          }
          if (!$(this).hasClass('selected')) {
            $(this).addClass('selected');
          } else {
            $(this).removeClass('selected');
          }
          Drupal.cdn.check_borders($(this));
          Drupal.cdn.validate_range($(this));
          Drupal.cdn.update_total($(this), settings);
        });
      });

      function cdn_change_village_capacity(val_v, val_c) {
        $('.cdn-village-teaser, .cdn-calendar, .cdn-no-results').hide();
        if (val_v !== 'all' || val_c !== 'all') {
          $('.cdn-calendar-list-row').each(function () {
            var i = $(this).parents('.cdn-calendar').data('index');
            // Filter only by village.
            if (val_v !== 'all' && val_c === 'all') {
              if ($(this).data('village_id') * 1 === val_v * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"], .cdn-calendar[data-index="' + i + '"]').show();
              }
            }
            // Filter only by capacity.
            if (val_v === 'all' && val_c !== 'all') {
              if ($(this).data('total_capacity') * 1 === val_c * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"], .cdn-calendar[data-index="' + i + '"]').show();
              }
            }
            // Filter by village and capacity.
            if (val_v !== 'all' && val_c !== 'all') {
              if ($(this).data('village_id') * 1 === val_v * 1 && $(this).data('total_capacity') * 1 === val_c * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"], .cdn-calendar[data-index="' + i +'"]').show();
              }
            }
          });
        }
        else if (val_v === 'all' && val_c === 'all') {
          $('.cdn-village-teaser, .cdn-calendar').show();
        }
        // if there are no results show a message.
        if ($('.cdn-village-teaser:visible').length === 0) {
          $('.cdn-no-results').show();
        }
      }
      // Search form filters.
      $('.cdn-form-full select[name="village"]').change(function () {
        var val_v = $(this).val(),
            val_c = $('.cdn-form-full select[name="capacity"]').val();
        cdn_change_village_capacity(val_v, val_c);
      });
      $('.cdn-form-full select[name="capacity"]').change(function () {
        var val_c = $(this).val(),
            val_v = $('.cdn-form-full select[name="village"]').val();
        cdn_change_village_capacity(val_v, val_c);
      });

      $('.cdn-village-teaser:eq(0)').addClass('active');
      $('.cdn-calendar:eq(0)').addClass('active');
      $('.cdn-village-teaser').on('click', function() {
        var index = $(this).data('index');
        $('.cdn-calendar, .cdn-village-footer-bar').removeClass('active').addClass('not-active');
        $('.cdn-calendar[data-index="' + index + '"]').addClass('active').removeClass('not-active');
        // Show footer only if there are selected dates.
        var footer = $('.cdn-village-footer-bar[data-index="' + index + '"]');
        if (footer.find('.nights').text()*1 !== 0) {
          footer.addClass('active').removeClass('not-active');
        }
        $('.cdn-village-teaser').removeClass('active').addClass('not-active');
        $(this).addClass('active').removeClass('not-active');
      });
      $('.cdn-calendar .close-cal').on('click', function(e) {
        e.preventDefault();
        var index = $(this).parents('.cdn-calendar').data('index');
        $(this).parents('.cdn-calendar').removeClass('active').addClass('not-active');
        $('.cdn-village-teaser[data-index="' + index + '"]').removeClass('active').addClass('not-active');
      });
      // Panorama handler.
      $('.panorama-wrapper .open-panorama').on('click', function (e) {
        e.preventDefault();
        $(this).parent().find('.panorama').animate({'height': '100%'});
      });
    }
  };

})(jQuery);