/**
 * @file
 * Form behavior.
 */
(function ($, window, Drupal) {

  'use strict';

  /**
   * Promo block settings form adjustments.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   * @prop {Drupal~behaviorMap} getIconUrl
   *   Maps fields values onto icon file url.
   */
  Drupal.behaviors.openyDigitalSignagePromoBlockFormPreview = {
    attach: function (context, settings) {
      $('.promo-block-preview', context).once().each(function () {
        var $self = $(this).empty();
        var $img = $('<img src="" class="promo-block-preview-icon">').appendTo($self);
        var path = settings.promo_block_form_preview.path;
        $self
          .parents('.field-group-fieldset')
          .find('.fieldset-wrapper')
          .css({position: 'relative'});

        $("[name=field_ds_message_position], [name=field_ds_layout]").on('change click', function() {
          var pos = $("[name=field_ds_message_position]:checked").val();
          var lay = $("[name=field_ds_layout]").val();
          $img.attr({
            src: Drupal.behaviors.openyDigitalSignagePromoBlockFormPreview.getIconUrl(pos, lay, path)
          });
        }).first().trigger('change');
      });
    },
    getIconUrl: function (position, layout, path) {
      var map_layout = {
        'l-left-50': 'landscape-a-',
        'l-bottom-50': 'landscape-b-',
        'p-top-33': 'portrait-a-',
        'p-bottom-33': 'portrait-b-'
      };
      return path + map_layout[layout] + position + '.png';
    }
  };

})(jQuery, window, Drupal);
