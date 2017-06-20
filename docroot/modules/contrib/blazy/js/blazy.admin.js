/**
 * @file
 * Provides admin utilities.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Blazy admin utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} form
   *   The Blazy form wrapper HTML element.
   */
  function blazyForm(i, form) {
    var t = $(form);

    $('.details-legend-prefix', t).removeClass('element-invisible');

    t[$('.form-checkbox--vanilla', t).prop('checked') ? 'addClass' : 'removeClass']('form--vanilla-on');

    t.on('click', '.form-checkbox', function () {
      var $input = $(this);
      $input[$input.prop('checked') ? 'addClass' : 'removeClass']('on');

      if ($input.hasClass('form-checkbox--vanilla')) {
        t[$input.prop('checked') ? 'addClass' : 'removeClass']('form--vanilla-on');
      }
    });

    $('select[name$="[style]"]', t).on('change', function () {
      var $select = $(this);

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--style-\S+/g) || []).join(' ');
      });

      if ($select.val() === '') {
        t.addClass('form--style-off');
      }
      else {
        t.addClass('form--style-on form--style-' + $select.val());
      }
    }).change();

    $('select[name$="[responsive_image_style]"]', t).on('change', function () {
      var $select = $(this);
      t[$select.val() === '' ? 'removeClass' : 'addClass']('form--responsive-image-on');
    }).change();

    $('select[name$="[media_switch]"]', t).on('change', function () {
      var $select = $(this);

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--media-switch-\S+/g) || []).join(' ');
      });

      t[$select.val() === '' ? 'removeClass' : 'addClass']('form--media-switch-' + $select.val());
    }).change();

    t.on('mouseenter touchstart', '.hint', function () {
      $(this).closest('.form-item').addClass('is-hovered');
    });

    t.on('mouseleave touchend', '.hint', function () {
      $(this).closest('.form-item').removeClass('is-hovered');
    });

    t.on('click', '.hint', function () {
      $('.form-item.is-selected', t).removeClass('is-selected');
      $(this).parent().toggleClass('is-selected');
    });

    t.on('click', '.description', function () {
      $(this).closest('.is-selected').removeClass('is-selected');
    });

    t.on('focus', '.js-expandable', function () {
      $(this).parent().addClass('is-focused');
    });

    t.on('blur', '.js-expandable', function () {
      $(this).parent().removeClass('is-focused');
    });
  }

  /**
   * Blazy admin tooltip function.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The Blazy form item description HTML element.
   */
  function blazyTooltip(i, elm) {
    var $tip = $(elm);

    if (!$tip.siblings('.hint').length) {
      $tip.closest('.form-item').append('<span class="hint">?</span>');
    }
  }

  /**
   * Attaches Blazy form behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyAdmin = {
    attach: function (context) {
      var $form = $('.form--slick', context);

      $('.description', $form).once('blazy-tooltip').each(blazyTooltip);

      $form.once('blazy-admin').each(blazyForm);
    }
  };

})(jQuery, Drupal);
