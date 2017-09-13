(function ($) {
  Drupal.ymca_cdn_cal = Drupal.ymca_cdn_cal || {};

  Drupal.ymca_cdn_cal.check_borders = function(cell) {
    //cell.parent().next().find('a').addClass('no-left-border');
  };

  Drupal.behaviors.ymca_cdn_cal = {
    attach: function (context, settings) {
      if ($('.paragraph--type--camp-du-nord-calendar-view').length > 0) {
        $('.fullcalendar').each(function() {
          $('.fc-day-top').each(function() {
            var number = $(this).find('.fc-day-number').text();
            var i = $(this).index();
            $(this)
              .attr('data-index', i);
            if ($(this).parents('table:eq(0)').find('tbody td:eq(' + i + ') .fc-day-grid-event').length === 1) {
              $(this)
                .hide()
                .parents('table:eq(0)')
                .find('tbody td:eq(' + i + ') .fc-day-grid-event')
                .find('.fc-content')
                .prepend('<i class="icon"></i><div class="fc-day-number">' + number + '</div>');
            }
            else {
              $(this)
                .hide()
                .parents('table:eq(0)')
                .find('tbody td:eq(' + i + ')')
                .prepend('<div class="fc-day-number">' + number + '</div>');
            }
          });
          $('a.cdn-prs-product').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!$(this).hasClass('selected')) {
              $(this).addClass('selected');
            }
            else {
              $(this).removeClass('selected');
            }
            Drupal.ymca_cdn_cal.check_borders($(this));
          });
        });
      }
    }
  };

})(jQuery);
