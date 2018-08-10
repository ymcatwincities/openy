(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.youtubeModal = {
        attach: function (context, settings) {
            $(".youtube-link").grtyoutube(
                {
                    autoPlay: true,
                    theme: "dark"
                }
            );

            $(".help-more").once().on('click', function(e) {
              e.preventDefault();
              var content = $(this).parent().find('.help-content');
              if (content.hasClass('hidden')) {
                content.removeClass('hidden');
                $(this).text(Drupal.t('Show less'));
              } else {
                content.addClass('hidden');
                $(this).text(Drupal.t('Show more'));
              }
            });

            // Show/hide help blocks by cookie.
            var hiddenArray = ($.cookie('AHB_hidden') !== undefined) ? $.parseJSON($.cookie('AHB_hidden')) : [];
            $('.block-help-item').each(function () {
              // Show if block is not in the hidden array.
              if ($.inArray($(this).data('block-id'), hiddenArray) === -1 || $.inArray($(this).data('block-id'), hiddenArray) === false) {
                $(this).removeClass('hidden');
              }
            });
            $('.help-close').once().on('click', function(e) {
              e.preventDefault();
              var block = $(this).parent();
              hiddenArray.push($(block).data('block-id'));
              $(block).addClass('hidden');
              $.cookie('AHB_hidden', JSON.stringify(hiddenArray), {'path': '/' + drupalSettings.path.currentPath});
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
