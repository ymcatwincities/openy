/**
 * @file
 * Slider and AJAX call for 'Activity Tracking' block.
 */

Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        function countEntry() {
            $('div.date-data').each(function (ind, val) {
                var itemDate = $(val).data('date');
                var entryContainer = $('.activity-data').find('div.' + itemDate);
                var entryCount = entryContainer.find('input[type="checkbox"]:checked').length;
                if (entryCount !== 0) {
                    $(val).find('.entriesNum').html(Drupal.t('You have @entryCount entries today', {'@entryCount': entryCount}));
                }
                return entryCount;
            });
        }

        function initActiveEl() {
            $('div.month').not('.slick-initialized').slick({
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
                            slidesToShow: 5,
                            slidesToScroll: 3
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 5,
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

            var todayInd = $('.now.slick-slide').index();
            $('div.month').slick('slickGoTo', parseInt(todayInd));

            var currentEl = $('.slick-current');
            var currentMonth = currentEl.find('span.month').html();
            $('h3.current-month').html(currentMonth);

            // set active element data.
            var activeElData = currentEl.data('date');
            openActivityData(activeElData);
            countEntry();

            // Disable future dates.
            var nowInd = null;
            $('.calendar-wrapper .slick-slide').each(function (ind, val) {
                if (nowInd !== null) {
                    $(val).addClass('disabled');
                }
                if ($(val).hasClass('now')) {
                    nowInd = ind;
                }
            });
            $('.calendar-wrapper .slick-slide.disabled').on('click', function (e) {
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
                var activityName = $(val).text();
                var icon = $(val).data('icon');
                var iconBackground = '';
                if (icon) {
                    iconBackground = ' style="background-image: url(' + icon + ');"';
                }

                $('.activity-data .categories ul').append(
                    '<li class="' + activityName.replace(/[ !"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, '') + '"' + iconBackground + '>' + activityName + '</li>'
                );
            });

            // Event to handle categories data.
            $('.activity-data .categories ul li').on('click', function (e) {
                $('.activity-data .categories ul li').removeClass('active');
                var activityName = $(e.target).attr('class');
                var className = '.' + activityName.replace(/[ !"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, '');
                $(e.target).addClass('active');
                $dataEl.find('.category-data').hide();
                $dataEl.find(className).parents('.category-data').show();
            });
        }

        $('.activity-data .activity-daily-data .category-data input.form-checkbox').on('change', function (e) {
            var formData = $(e.target).closest('form');
            var data = $(e.target).parents('.activity-daily-data').find('input[name="date"]').val();

            $.post(
                drupalSettings.path.baseUrl + 'campaign-save-activity/' + data,
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

        // Iterate by activities that supports counter.
        $('.activities-count .count-value').map(
            function () {
                var _$self = $(this);
                var activityId = $(this).data('activityid');
                var activityDate = $(this).data('date');
                var counterValue = this.value;
                var relatedActivity = $('.activity-daily-data.' + activityDate).find('.form-item-activities-' + activityId);
                // Create pseudo input in activity wrapper to interact with hidden counter value.
                var input = document.createElement("input");
                input.type = "text";
                input.value = counterValue;
                input.className = "preudo-activity-counter";
                $(relatedActivity).addClass('counted');
                $(relatedActivity).append(input);
                // Update counter value by pressing enter.
                $(relatedActivity).on('keypress', function (e) {
                  if (e.keyCode == 13) {
                      e.preventDefault();
                      _$self.val($(this).find('.preudo-activity-counter').val());
                      relatedActivity.find('input.form-checkbox').trigger('change');
                      $(this).find('.preudo-activity-counter').blur();
                      return false;
                  }
                });
                // Update counter value by loosing focus from the input.
                $(relatedActivity).on('focusout', function () {
                    _$self.val($(this).find('.preudo-activity-counter').val());
                    relatedActivity.find('input.form-checkbox').trigger('change');
                });
                // Make activity checked by user focus.
                $(relatedActivity).on('focusin', function () {
                    relatedActivity.find('input.form-checkbox').attr('checked', 'checked');
                });
            }
        );

    }

};
