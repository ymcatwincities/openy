(function($) {
  "use strict";

  Drupal.behaviors.openy_lily_subcategory_classes_theme = {
    attach: function(context, settings) {

      $(document)
        .once()
        .ajaxSuccess(function(e, xhr, settings) {
          if (settings.data !== undefined && settings.data.match('view_name=sub_category_classes&view_display_id=search_form')) {
            var view = $('.sub-category-classes-view');
            view.find('.js-form-type-select select').removeAttr('readonly');
            view.find('.filters-container').addClass('hidden');
            if (view.find('.filter').length !== 0) {
              view.find('.filters-container').removeClass('hidden');
            }
          }
        });

      $('.sub-category-classes-view').once().each(function() {
        var view = $(this);

        // Initialize Slick.
        view.find('.activity-group-slider').slick({
          dots: true,
          infinite: false,
          speed: 300,
          slidesToShow: 3,
          slidesToScroll: 3,
          prevArrow: '<button type="button" class="slick-prev" value="' + Drupal.t('Previous') + '" title="' + Drupal.t('Previous') + '">' + Drupal.t('Previous') + '<i class="fa fa-chevron-left" aria-hidden="true"></i></button>',
          nextArrow: '<button type="button" class="slick-next" value="' + Drupal.t('Next') + '" title="' + Drupal.t('Next') + '">' + Drupal.t('Next') + '<i class="fa fa-chevron-right" aria-hidden="true"></i></button>',
          customPaging: function(slider, i) {
            return '<button type="button" data-role="none" aria-hidden="true" role="button" tabindex="' + i + '" value="' + Drupal.t('Slide set @i', {'@i': i+1}) + '" title="' + Drupal.t('Slide set @i', {'@i': i+1}) + '">' + (i+1) + '</button>';
          },
          responsive: [
            {
              breakpoint: 992,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                infinite: true,
                dots: true,
                arrows: true
              }
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                dots: true,
                arrows: true
              }
            }
          ]
        });

        // Filters actions.
        view.find('.add-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container, .actions-wrapper').removeClass('hidden-xs');
            view.find('.close-filters').removeClass('hidden');
            view.find('.filters-container').addClass('hidden');
            $(this).addClass('hidden');
        });
        view.find('.close-filters')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.selects-container, .actions-wrapper').addClass('hidden-xs');
            view.find('.add-filters').removeClass('hidden');
            view.find('.filters-container').removeClass('hidden');
            $(this).addClass('hidden');
          });

        view.find('.js-form-type-select select')
          .change(function() {
            if ($(window).width() > 767) {
              view.find('.js-form-type-select select').attr('readonly', true);
              view.find('form .form-actions input:eq(0)').trigger('click');
            }
          });

        view.find('.filter .remove')
          .on('click', function(e) {
            e.preventDefault();
            view.parents('.filter').remove();
            view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            if (view.find('.filter').length === 0) {
              view.find('.filters-container').addClass('hidden');
            }
            view.find('.js-form-type-select select').attr('readonly', true);
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
          });

        view.find('.clear')
          .on('click', function(e) {
            e.preventDefault();
            view.find('.filters-container').find('a.remove').each(function() {
              view.find('select option[value="' + $(this).data('id') + '"]').attr('selected', false);
            });
            view.find('.js-form-type-select select').attr('readonly', true);
            view.find('.actions-wrapper').find('input:eq(0)').trigger('click');
          });
      });
    }
  };
})(jQuery);
