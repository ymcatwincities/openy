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

  Drupal.cdn.check_borders = function(cell) {
    cell.parents('.fc-view').find('a').removeClass('no-right-border').removeClass('no-left-border').removeClass('first-selected').removeClass('last-selected');
    if (cell.parents('.fc-view').find('a.selected').length > 1) {
      cell.parents('.fc-view').find('a.selected').addClass('no-right-border').addClass('no-left-border');
      cell.parents('.fc-view').find('a.selected:first').addClass('no-right-border first-selected').removeClass('no-left-border');
      var last = cell.parents('.fc-view').find('a.selected:last');
      last.addClass('no-left-border last-selected').removeClass('no-right-border');
      if (last.find('.fc-content .tip').length === 0) {
        last.find('.fc-content').append('<div class="tip"><span>' + Drupal.t('You pay only per night') + '</span></div>');
        $('<div class="tip-q">$0<span class="q-mark"><i class="fa fa-question-circle"></i></span></div>').insertAfter(last.find('.fc-title'));
      }
    }
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
  };

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

      $('a.cdn-prs-product', context).each(function() {
        // Check id date is booked.
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
          Drupal.cdn.update_total($(this), settings);
        });
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
    }
  };

})(jQuery);