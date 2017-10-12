
        Drupal.behaviors.activityTracking = {
            attach: function (context, settings) {

                var $ = jQuery;

                function setActiveEl($el) {
                    $('.month li').removeClass('active');
                    $el.addClass('active')
                        .attr('style', 'display: inline-block');
                    var currentActiveElPos = $el.position();
                    $el.find('.visits').css('left', currentActiveElPos.left - 105);
                }

                var activeItemIndex;
                var nextElIndex;
                var prevElIndex;
                var $listModel;
                var categoryHtml;


                $listModel = $('.month li');

                $listModel.each(function(index, val) {
                    if ($(val).hasClass('now')) {
                        activeItemIndex = index;
                    }
                });


                for (var i = activeItemIndex - 5; i <= activeItemIndex+1; i++) {
                    $($listModel[i]).attr('style', 'display: inline-block');
                }


                // Today is active by default.
                var currentActiveEl =  $($listModel[activeItemIndex]);
                setActiveEl(currentActiveEl);


                nextElIndex = activeItemIndex + 1;
                prevElIndex = activeItemIndex - 6;

                /** Events */
                $('.calendar-wrapper .next').on('click', function(e) {
                    $($listModel[nextElIndex]).attr('style', 'display: inline-block');
                    $($listModel[prevElIndex]).attr('style', 'display: none');
                    nextElIndex++;
                    prevElIndex++;
                    // increase activeElIndex
                    activeItemIndex++;
                    setActiveEl($($listModel[activeItemIndex]));
                });

                $('.calendar .prev').on('click', function(e) {
                    $($listModel[prevElIndex]).attr('style', 'display: inline-block');
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

                    $dataEl.find('.category-data label').on('click', function(e){
                       // $(e.target).parents('form').find('.form-submit').click();
                        $(e.target).prev('.form-checkbox').click();
                        //console.log($(e.target).prev('.form-checkbox'));
                        var formData = $(e.target).parents('form');
                        var data = $(e.target).parents('.activity-daily-data').find('input[name="date"]').val()
                        console.log(data);
                        $.post(
                            '/campaign-save-activity',
                            { data: data, activities: formData.serialize() },
                            function(data) {
                                console.log(data);
                             }
                        );

                    });

                    var categories = $dataEl.find('.activity-name');

                    // Complete categories list.
                    $('.activity-data .categories ul').html('');
                    categories.each(function (ind, val) {
                        var activityName = $(val).html();
                        /*$('.activity-data .category-data').append(
                            $(val).parent().find('form').addClass(
                                activityName
                            ).hide()
                        );*/
                        $('.activity-data .categories ul').append(
                            '<li class="' + activityName + '">' + activityName + '</li>'
                        );
                    });

                    // Event to handle categories data.
                    $('.activity-data .categories ul li').on('click', function(e) {

                        $('.activity-data .categories ul li').removeClass('active');
                        var activityName = $(e.target).attr('class');
                        $(e.target).addClass('active');
                        $dataEl.find('.category-data').hide();
                        $dataEl.find('.' + activityName).parents('.category-data').show();
                        //console.log($dataEl.find('.' + activityName).parents('.category-data'));
                    });

                }

                $('.calendar ul li.date-data').on('click', function(e) {
                    // remove active class from previous el.
                    $('.calendar li.date-data').removeClass('active');
                    // set class to active elm.
                    var currentActiveEl = $(e.target).parent('li');
                    currentActiveEl.addClass('active');
                    // get active's elm position and set offset for visits container.
                    var currentActiveElPos = currentActiveEl.position();
                    currentActiveEl.find('.visits').css('left', currentActiveElPos.left - 105);

                    var activeDate = currentActiveEl.data('date');
                    openActivityData(activeDate);




                    /*categoryHtml = $(e.target).parent('li').find('.categories').html();
                    //$('.activity-data').html(categoryHtml);

                    var categories = $('.activity-name', categoryHtml);

                    // Initiate categories list.
                    $('.activity-data .categories').html('<ul></ul>');
                    $('.activity-data .category-data').html('');

                    categories.each(function (ind, val) {
                        var activityName = $(val).html();

                        $('.activity-data .category-data').append(
                            $(val).parent().find('form').addClass(
                                activityName
                            ).hide()
                        );
                        $('.activity-data .categories ul').append(
                            '<li class="' + activityName + '">' + activityName + '</li>'
                        );
                    });

                    $('.activity-data .categories ul li').on('click', function (e) {
                        //console.log(e.target);
                        $(e.target).parent('ul').find('li').removeClass('active');
                        var categoryClass = $(e.target).attr('class');
                        $(e.target).addClass('active');
                        $('.activity-data .category-data form').hide();
                        $('.activity-data .category-data .' + categoryClass).show();
                    });

                    $('.category-data form .form-item label').on('click', function (e) {
                        console.log('form submit');
                    });

                });*/
            });
        }
    }

