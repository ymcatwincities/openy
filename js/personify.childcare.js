/**
 * @file
 */

(function ($) {

  "use strict";

  /**
   * Attach behaviors to Childcare functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.personify_childcare = {
    attach: function(context, settings) {
      $('.childcare-payment-history-form .js-form-item-start-date input').datepicker({
        changeYear: true,
        minDate: '-2Y',
        maxDate: '+0D',
        onSelect: function(dateText, ins) {
          $('.childcare-payment-history-form', context)
            .each(function() {
              var form = $(this);
              form.find('.js-form-submit').trigger('click');
          });
        }
      });
      $('.childcare-payment-history-form .js-form-item-end-date input').datepicker({
        minDate: '-2Y',
        maxDate: '+0D',
        onSelect: function(dateText, ins) {
          $('.childcare-payment-history-form', context)
            .each(function() {
              var form = $(this);
              form.find('.js-form-submit').trigger('click');
          });
        }
      });

      $('.childcare-payment-history-form .js-form-item-child select', context).on('change', function() {
        var val = $(this).val();
        if (val !== 'all') {
          $('table.child').hide();
          $('table.child-' + val).show();
          $('.total').hide();
        }
        else {
          $('table.child').show();
          $('.total').show();
        }
      });

      $('#childcare-payment-history-form-wrapper .download_pdf', context).on('click', function(e) {
        var val = $('.childcare-payment-history-form .js-form-item-child select').val(),
            href = $(this).attr('href').replace(/child=(.*)/g, 'child=' + val);
        $(this).attr('href', href);
      });
    }
  };

})(jQuery);
