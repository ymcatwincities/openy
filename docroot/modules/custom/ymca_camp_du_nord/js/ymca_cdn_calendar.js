(function($) {
  Drupal.cdn = Drupal.cdn || {};

  Drupal.cdn.check_borders = function(cell) {
    if (cell.parents('.fc-view').find('a.selected').length > 1) {
      cell.parents('.fc-view').find('a.selected').removeClass('no-right-border').removeClass('no-left-border').removeClass('first-selected').removeClass('last-selected');
      cell.parents('.fc-view').find('a.selected').addClass('no-right-border').addClass('no-left-border');
      cell.parents('.fc-view').find('a.selected:first').addClass('no-right-border first-selected').removeClass('no-left-border');
      cell.parents('.fc-view').find('a.selected:last').addClass('no-left-border last-selected').removeClass('no-right-border');
    }
  };

  Drupal.behaviors.cdn = {
    attach: function(context, settings) {
      $('.fullcalendar', context).each(function() {
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
        $(this).addClass('processed');
      });

      $('a.cdn-prs-product', context).each(function() {
        $(this).on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          if (!$(this).hasClass('selected')) {
            $(this).addClass('selected');
          } else {
            $(this).removeClass('selected');
          }
          Drupal.cdn.check_borders($(this));
        });
      });

      if (typeof(settings.cdn.selected_dates) !== 'undefined') {
        var dates = settings.cdn.selected_dates;
        for (var i = 0; i < dates.length; i++) {
          var link = $('a.cdn-prs-product.' + dates[i]);
          link.addClass('selected');
          Drupal.cdn.check_borders(link);
        }
      }

      $('.cdn-village-teaser:eq(0)').addClass('active');
      $('.cdn-calendar:eq(0)').addClass('active');
      $('.cdn-village-teaser').on('click', function() {
        var index = $(this).data('index');
        $('.cdn-calendar').removeClass('active').addClass('not-active');
        $('.cdn-calendar[data-index="' + index + '"]').addClass('active').removeClass('not-active');
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