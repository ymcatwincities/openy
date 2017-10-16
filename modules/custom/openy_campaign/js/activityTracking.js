Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        var $ = jQuery;
        var activeItemIndex;
        var nextElIndex;
        var prevElIndex;
        var $listModel = $('.month li');
        var currentActiveEl;

        function setActiveEl($el) {
            var currentActiveElPos = $el.offset();
            $('.month li').removeClass('active');
            $el.addClass('active');


            $('ul.month').scrollLeft(currentActiveElPos.left - 100);

            var currentActiveElPosVis = $el.position();

            $el.find('.visits').css('left', currentActiveElPosVis.left - 105);
        }

        function initActiveEl() {
            $listModel.each(function (index, val) {
                if ($(val).hasClass('now')) {
                    activeItemIndex = index;
                }
            });
            // Today is active by default.
            currentActiveEl = $($listModel[activeItemIndex]);
            setActiveEl(currentActiveEl);

            nextElIndex = activeItemIndex + 1;
            prevElIndex = activeItemIndex - 1;
        }


        // Initiate default Active Item in calendar.
        initActiveEl();



        /** Events */
        $('.calendar-wrapper .next').on('click', function (e) {
            console.log('next');
            nextElIndex++;
            prevElIndex++;
            // increase activeElIndex
            activeItemIndex++;
            setActiveEl($($listModel[activeItemIndex]));
        });

        $('.calendar .prev').on('click', function (e) {

            nextElIndex--;
            prevElIndex--;
            // increase activeElIndex
            activeItemIndex--;
            setActiveEl($($listModel[activeItemIndex]));
        });

        function openActivityData(date) {
            $('.activity-data').find('.activity-daily-data').hide();
            var $dataEl = $('.activity-data').find('.' + date);

            $dataEl.show();

            $dataEl.find('.category-data label').on('click', function (e) {
                $(e.target).prev('.form-checkbox').click();
                var formData = $(e.target).parents('form');
                var data = $(e.target).parents('.activity-daily-data').find('input[name="date"]').val();
                $.post(
                    '/campaign-save-activity/' + data,
                    formData.serialize(),
                    function (data) {
                        console.log(data);
                    }
                );

            });

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

        $('.calendar ul li.date-data').on('click', function (e) {
            // remove active class from previous el.
            $('.calendar li.date-data').removeClass('active');
            // set class to active elm.
            var currentActiveEl = $(e.target).parent('li');
            currentActiveEl.addClass('active');
            // get active's elm position and set offset for visits container.
            var currentActiveElPos = currentActiveEl.position();
            console.log(currentActiveElPos.left);
            currentActiveEl.find('.visits').css('left', currentActiveElPos.left - 105);

            var activeDate = currentActiveEl.data('date');
            openActivityData(activeDate);
        });
    }
}
