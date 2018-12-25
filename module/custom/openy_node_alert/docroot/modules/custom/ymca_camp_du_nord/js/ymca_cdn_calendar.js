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

  function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
      return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
      return uri + separator + key + "=" + value;
    }
  }

  Drupal.cdn = Drupal.cdn || {};

  Drupal.cdn.check_borders_mobile = function(cell) {
    cell.parents('.cdn-calendar-list').find('.tip').remove();
    if (cell.parents('.cdn-calendar-list').find('a.cdn-prs-product-mobile.selected').length > 1) {
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

  /**
   * Extract date object from element.
   *
   * @param element
   * @returns {Date}
   *   Date object.
   */
  function getDateFromCell(element) {
    if (element.attr('class') === undefined) {
      return null;
    }

    var classes = element.attr('class').split(" ");

    // Find class similar to date. Ex. 2018-04-30.
    var currentYear = new Date().getFullYear();
    var dateClasses = classes.filter(function (item) {
      if (item.substring(0, 4) == currentYear) {
        return true;
      }

      if (item.substring(0, 4) == currentYear + 1) {
        return true;
      }
    });

    return new Date(dateClasses[0]);
  }

  /**
   * Get string date from Date object.
   *
   * @param date
   * @returns {string}
   */
  function getStringFromDate(date) {
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth()+1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    return year + "-" + month + "-" + day;
  }

  Drupal.cdn.validate_range = function(cell) {
    var products = cell.parents('.fc-view').find('a.cdn-prs-product');

    var selectedDateFirst = getDateFromCell(cell.parents('.fc-view').find('a.selected:first'));
    var selectedDateLast = getDateFromCell(cell.parents('.fc-view').find('a.selected:last'));

    // Select all dates we have to join.
    // @todo Refactor to the function.
    var list = [];
    var iterateDate = selectedDateFirst;
    if (iterateDate === null) {
      return;
    }

    var iterateDateTime = iterateDate.getTime();
    var selectedDateLastTime = selectedDateLast.getTime();
    while (iterateDateTime <= selectedDateLastTime) {
      iterateDate = new Date(iterateDateTime);
      var iterateUTCDate = iterateDate.getUTCDate();
      list.push(getStringFromDate(iterateDate));
      iterateDate.setUTCDate(iterateUTCDate + 1);
      iterateDateTime = iterateDate.getTime();
    }

    // Go through each product end ensure range is not splitted.
    products.each(function() {
      var iterateDate = getDateFromCell($(this));
      var iterateClass = getStringFromDate(iterateDate);
      if (list.length > 1 && $.inArray(iterateClass, list) !== -1 && !$(this).hasClass('selected') ) {
        $(this).addClass('selected');
        // Disallow selection if booked days in range.
        if ($(this).hasClass('booked')) {
          $(this).removeClass('selected');
        }
      }
    });

  };

  Drupal.cdn.validate_range_mobile = function(cell) {
    var products = cell.parents('.cdn-calendar-list').find('a.cdn-prs-product-mobile');

    var selectedDateFirst = getDateFromCell(cell.parents('.cdn-calendar-list').find('a.selected:first'));
    var selectedDateLast = getDateFromCell(cell.parents('.cdn-calendar-list').find('a.selected:last'));

    // Select all dates we have to join.
    var list = [];
    var iterateDate = selectedDateFirst;
    if (iterateDate === null) {
      return;

    }

    // Select all dates we have to join.
    // @todo Refactor to the function.
    var iterateDateTime = iterateDate.getTime();
    var selectedDateLastTime = selectedDateLast.getTime();
    while (iterateDateTime <= selectedDateLastTime) {
      iterateDate = new Date(iterateDateTime);
      var iterateUTCDate = iterateDate.getUTCDate();
      list.push(getStringFromDate(iterateDate));
      iterateDate.setUTCDate(iterateUTCDate + 1);
      iterateDateTime = iterateDate.getTime();
    }

    // Go through each product end ensure range is not splitted.
    products.each(function() {
      var iterateDate = getDateFromCell($(this));
      var iterateClass = getStringFromDate(iterateDate);
      if (list.length > 1 && $.inArray(iterateClass, list) !== -1 && !$(this).hasClass('selected') ) {
        $(this).addClass('selected');

        // Disallow selection if booked days in range.
        if ($(this).parent().hasClass('booked')) {
          $(this).removeClass('selected');
        }
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
    footer.parents('.cdn-village-footer').addClass('active');
    if (nights === 0) {
      footer.addClass('not-active').removeClass('active');
      footer.parents('.cdn-village-footer').removeClass('active');
    }
    // To do: update related desktop calendar.
  };

  Drupal.cdn.update_total = function(link, settings) {
    var calendar = link.parents('.cdn-calendar[data-index]'),
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
    footer.parents('.cdn-village-footer').addClass('active');
    if (nights === 0) {
      footer.addClass('not-active').removeClass('active');
      footer.parents('.cdn-village-footer').removeClass('active');
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
      eventStartEditable: false,
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

      // Submit form on selecting specific cabin.
      $('#edit-cabin').once('change').on('change', function () {
        $('#edit-submit').click();
      });

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
              var text = '<div class="fc-day-number">' + number + '</div>';
              if ($.inArray($(this).data('date'), settings.cdn.selected_dates) !== -1) {
                text = '<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end fc-event-default cdn-prs-product fc-event-past fc-draggable ' + settings.cdn.selected_dates[$.inArray($(this).data('date'), settings.cdn.selected_dates)] + ' booked fake-booked" href="#"><div class="fc-content"><div class="fc-day-number">'+number+'</div><span class="fc-time">12a</span> <span class="fc-title"></span></div></a>';
              }
              $(this)
                .hide()
                .parents('table:eq(0)')
                .find('tbody td:eq(' + i + ')')
                .prepend(text);
            }
          }
        });
      });

      // Handles mobile view dates selection functionality.
      $('a.cdn-prs-product-mobile', context).each(function() {
        $(this).on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          var allowSelection = true;
          if ($(this).parent().hasClass('booked')) {
            allowSelection = false;
            // Check if previous date is selected and give a chance to select booked date as checkout date.
            var target = $(this).parent().prev();
            if (target.length !== 0) {
              if (target.find('a.cdn-prs-product-mobile').hasClass('selected') && !target.hasClass('booked')) {
                allowSelection = true;
              }
            }
          }
          // Block select option if out of range.
          if ($('a.cdn-prs-product-mobile.selected', context).length >= 2) {
            var prev_sibling_s = $(this).parent().prev().find('a.cdn-prs-product-mobile'),
                next_sibling_s = $(this).parent().next().find('a.cdn-prs-product-mobile');
            if ((prev_sibling_s.length === 1 && prev_sibling_s.length === 1) && !prev_sibling_s.hasClass('selected') && !next_sibling_s.hasClass('selected')) {
              allowSelection = false;
            }
          }
          if (!$(this).hasClass('selected')) {
            if (!allowSelection) {
              return;
            }
            $(this).addClass('selected');
          } else {
            // Check if date is not in the middle of selected range.
            var left_sibling = $(this).parent().prev().find('a.cdn-prs-product-mobile'),
                right_sibling = $(this).parent().next().find('a.cdn-prs-product-mobile');
            if (left_sibling.hasClass('selected') && right_sibling.hasClass('selected')) {
              return;
            }
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
          var allowSelection = true;
          if ($(this).hasClass('booked')) {
            allowSelection = false;
            // Check if previous date is selected and give a chance to select booked date as checkout date.
            var i = $(this).index('a.cdn-prs-product');
            if (i !== 0) {
              var target = $('a.cdn-prs-product:eq(' + (i - 1) + ')', context);
              if (target.hasClass('selected') && !target.hasClass('booked')) {
                allowSelection = true;
              }
            }
          }
          // Block select option if out of range.
          if ($('a.cdn-prs-product.selected', context).length >= 2) {
            var k = $(this).find('.fc-day-number').index('.fc-content-skeleton div.fc-day-number'),
                left_sibling_s = $('.fc-content-skeleton div.fc-day-number:eq(' + (k - 1) + ')', context),
                right_sibling_s = $('.fc-content-skeleton div.fc-day-number:eq(' + (k + 1) + ')', context);
            if ((left_sibling_s.length === 1 && right_sibling_s.length === 1) && left_sibling_s.parents('.selected').length === 0 && right_sibling_s.parents('.selected').length === 0) {
              allowSelection = false;
            }
          }
          if (!$(this).hasClass('selected')) {
            if (!allowSelection) {
              return;
            }
            $(this).addClass('selected');
          } else {
            // Check if date is not in the middle of selected range.
            var j = $(this).index('a.cdn-prs-product');
            if (j !== 0) {
              var left_sibling = $('a.cdn-prs-product:eq(' + (j - 1) + ')', context),
                  right_sibling = $('a.cdn-prs-product:eq(' + (j + 1) + ')', context);
              if (left_sibling.hasClass('selected') && right_sibling.hasClass('selected')) {
                return;
              }
            }
            $(this).removeClass('selected');
          }
          Drupal.cdn.check_borders($(this));
          Drupal.cdn.validate_range($(this));
          Drupal.cdn.update_total($(this), settings);
        });
      });

      function cdn_change_village_capacity(val_v, val_c) {
        $('.cdn-village-teaser, .cdn-calendar, .cdn-no-results, .cdn-results-active-wrapper').hide();
        if (val_v !== 'all' || val_c !== 'all') {
          $('.cdn-village-teaser').each(function () {
            var i = $(this).data('index');
            // Filter only by village.
            if (val_v !== 'all' && val_c === 'all') {
              if ($(this).data('village_id') * 1 === val_v * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"]').show();
                $('.cdn-calendar[data-index="' + i + '"]').show();
                $('.cdn-village-teaser[data-index="' + i + '"]').parents('.cdn-results-active-wrapper').show();
              }
            }
            // Filter only by capacity.
            if (val_v === 'all' && val_c !== 'all') {
              if ($(this).data('total_capacity') * 1 === val_c * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"]').show();
                $('.cdn-calendar[data-index="' + i + '"]').show();
                $('.cdn-village-teaser[data-index="' + i + '"]').parents('.cdn-results-active-wrapper').show();
              }
            }
            // Filter by village and capacity.
            if (val_v !== 'all' && val_c !== 'all') {
              if ($(this).data('village_id') * 1 === val_v * 1 && $(this).data('total_capacity') * 1 === val_c * 1) {
                $('.cdn-village-teaser[data-index="' + i + '"]').show();
                $('.cdn-calendar[data-index="' + i + '"]').show();
                $('.cdn-village-teaser[data-index="' + i + '"]').parents('.cdn-results-active-wrapper').show();
              }
            }

          });
        }
        else if (val_v === 'all' && val_c === 'all') {
          $('.cdn-village-teaser, .cdn-calendar').show();
        }
        // Update queries with selected values.
        $('.cdn-village-teaser > a').each(function () {
          var q = $(this).attr('href');
          q = updateQueryStringParameter(q, 'village', val_v);
          q = updateQueryStringParameter(q, 'capacity', val_c);
          $(this).attr('href', q);
        });
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

      // Show active cabin.
      var q_cid = getParameterByName('cid', window.location.search);
      if (q_cid) {
        $('.cdn-village-teaser[data-index="' + q_cid + '"]').addClass('active');
        $('.cdn-calendar[data-index="' + q_cid + '"]').addClass('active');
      }
      else {
        $('.cdn-village-teaser:eq(0)').addClass('active');
        $('.cdn-calendar:eq(0)').addClass('active');
      }
      // Part of functionality without page reload (uncomment for using).
      /*$('.cdn-village-teaser').on('click', function() {
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
      });*/
      // Panorama handler.
      $('.panorama-wrapper .open-panorama').on('click', function (e) {
        e.preventDefault();
        $(this).parent().find('.panorama').animate({'height': '100%'});
      });
    }
  };

})(jQuery);