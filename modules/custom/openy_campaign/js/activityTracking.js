Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        var $ = jQuery;

        function initActiveEl() {
            $('div.month').slick({
                infinite: true,
                slidesToShow: 7,
                slidesToScroll: 7,
                centerMode: true,
                focusOnSelect: true,
                centerPadding: '0px',
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            infinite: true,
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
                    // You can unslick at a given breakpoint now by adding:
                    // settings: "unslick"
                    // instead of a settings object
                ]
            });
            var currentEl = $('.slick-current');
            var currentMonth = currentEl.find('span.month').html();
            $('h3.current-month').html(currentMonth);

            // set active element data.
            var activeElData = currentEl.data('date');
            openActivityData(activeElData);
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
               console.log(data);
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
