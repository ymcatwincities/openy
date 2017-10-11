Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        var $ = jQuery;
        var activeItemIndex;
        var nextElIndex;
        var prevElIndex;
        var $listModel;
        var categoryHtml;


        $listModel = $('.month li');

        $listModel.each(function(index, val) {
            if ($(val).hasClass('active')) {
                activeItemIndex = index;
            }
        });

        $('.month li').removeClass('active');
        $($listModel[activeItemIndex - 3]).attr('style', 'display: inline-block');

        for (var i = activeItemIndex - 6; i < activeItemIndex; i++) {
            $($listModel[i]).attr('style', 'display: inline-block');
        }
        nextElIndex = activeItemIndex + 1;
        prevElIndex = activeItemIndex - 6;

        /** Events */
        $('.calendar .next').on('click', function(e) {
            jQuery($listModel[nextElIndex]).attr('style', 'display: inline-block');
            jQuery($listModel[prevElIndex]).attr('style', 'display: none');
            nextElIndex++;
            prevElIndex++;
        });

        $('.calendar .prev').on('click', function(e) {
            jQuery($listModel[prevElIndex]).attr('style', 'display: inline-block');
            nextElIndex--;
            prevElIndex--;
        });

        $('.calendar li.date-data').on('click', function(e) {

            $('.calendar li.date-data').removeClass('active');

            $(e.target).parent('li').addClass('active');
            var leftOffset = $(e.target).parent('li').offset();

            $(e.target).parent('li').find('.visits').css('left', leftOffset.left - 250);

            categoryHtml = $(e.target).parents('li').find('.categories').html();
            //$('.activity-data').html(categoryHtml);
            var categories = $('.activity-name', categoryHtml);

            // Initiate categories list.
            $('.activity-data .categories').html('<ul></ul>');
            $('.activity-data .category-data').html('');

            categories.each(function(ind, val) {
                var activityName = $(val).html();

                $('.activity-data .category-data').append(
                    $(val).parent().find('form').addClass(
                        $(val).html()
                    ).hide()
                );
                $('.activity-data .categories ul').append(
                    '<li class="'+ activityName +'">' + $(val).html() + '</li>'
                );
            });
            $('.activity-data .categories ul li').on('click', function(e) {

                var categoryClass = $(e.target).attr('class');
                $('.activity-data .category-data form').hide();
                $('.activity-data .category-data .' + categoryClass).show();
            });

        });
    }
}
