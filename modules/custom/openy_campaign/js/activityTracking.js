Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        var $ = jQuery;

        function countEntry() {
            jQuery('div.date-data').each( function(ind,val) {
              var itemDate = jQuery(val).data('date');
              var entryContainer = jQuery('.activity-data').find('div.' + itemDate);
              var entryCount = entryContainer.find('input[type="checkbox"]:checked').length;
              if (entryCount !== 0) {
                  $(val).find('.entriesNum').html('You have ' + entryCount + ' entries today');
              }
              return entryCount;
           });
        }

        function initActiveEl() {
            $('div.month').slick({
                infinite: false,
                slidesToShow: 7,
                slidesToScroll: 7,
                centerMode: true,
                initialSlide: 0,
                focusOnSelect: true,
                centerPadding: '0px',
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            //infinite: true,
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3
                        }
                    }
                ]
            });
            var currentEl = $('.slick-current');
            var currentMonth = currentEl.find('span.month').html();
            $('h3.current-month').html(currentMonth);

            // set active element data.
            var activeElData = currentEl.data('date');
            openActivityData(activeElData);
            countEntry();

            // Disable future dates.
            var nowInd = null;
            $('.calendar-wrapper .slick-slide').each(function(ind, val) {
                if (nowInd !== null) {
                  $(val).addClass('disabled');
                }
                if ($(val).hasClass('now')) {
                  nowInd = ind;
                }
            });
            $('.calendar-wrapper .slick-slide.disabled').on('click', function(e) {
                e.preventDefault();
                $(e.target).parent('.date-data').removeClass('slick-current');
            });
        }

        // Initiate default Active Item in calendar.
        initActiveEl();

        /** Events */
        function openActivityData(date) {
            $('.activity-data').find('.activity-daily-data').hide();
            var $dataEl = $('.activity-data').find('.' + date);

            $dataEl.show();

            var categories = $dataEl.find('.activity-name');
            // Complete categories list.
            $('.activity-data .categories ul').html('');
            categories.each(function (ind, val) {
                var activityName = $(val).html();
                $('.activity-data .categories ul').append(
                    '<li class="' + activityName.replace(/ /g, '') + '">' + activityName + '</li>'
                );
            });

            // Event to handle categories data.
            $('.activity-data .categories ul li').on('click', function (e) {
                $('.activity-data .categories ul li').removeClass('active');
                var activityName = $(e.target).attr('class');
                $(e.target).addClass('active');
                $dataEl.find('.category-data').hide();
                $dataEl.find('.' + activityName.replace(/ /g, '')).parents('.category-data').show();
            });
        }

        $('.activity-data .activity-daily-data .category-data input').on('change', function (e) {

            var formData = $(e.target).closest('form');
            var data = $(e.target).parents('.activity-daily-data').find('input[name="date"]').val();

            $.post(
             '/campaign-save-activity/' + data,
             formData.serialize(),
             function (data) {
                 countEntry();
               }
             );
        });

        $('.calendar .month .date-data').on('click', function (e) {
            var currentActiveEl = $(e.target).parent('div.date-data');
            var activeDate = currentActiveEl.data('date');
            var currentMonth = currentActiveEl.find('span.month').html();
            $('h3.current-month').html(currentMonth);
            openActivityData(activeDate);
        });
    }
}
