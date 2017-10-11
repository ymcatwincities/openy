Drupal.behaviors.activityTracking = {
    attach: function (context, settings) {

        var $ = jQuery;
        var activeItemIndex;
        var nextElIndex;
        var prevElIndex;
        var $listModel;
        var categoryHtml;

        $('.month .active').attr('style', 'display: block').focus();
        $listModel = $('.month li');

        $listModel.each(function(index, val) {
            if ($(val).hasClass('active')) {
                activeItemIndex = index;
            }
        });

        for (var i = activeItemIndex - 2; i < activeItemIndex + 3; i++) {
            $($listModel[i]).attr('style', 'display: inline-block');
        }
        nextElIndex = activeItemIndex + 3;
        prevElIndex = activeItemIndex - 3;

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
            categoryHtml = $(e.target).parents('li').find('.categories').html();
            //$('.activity-data').html(categoryHtml);
            var categories = $('.activity-name', categoryHtml);

            // Initiate categories list.
            $('.activity-data .categories').html('<ul></ul>');
            categories.each(function(ind, val) {
                $('.activity-data .categories ul').append('<li>' + $(val).html() + '</li>');
                $('.activity-data .category-data').append('');
            });

        });

    }
}
