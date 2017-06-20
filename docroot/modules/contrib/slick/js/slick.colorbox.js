/**
 * @file
 * Provides Colorbox integration.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.slickColorbox = {
    attach: function (context) {
      $(context).on('cbox_open', function () {
        Drupal.slickColorbox.set('slickPause');
      });

      $(context).on('cbox_load', function () {
        Drupal.slickColorbox.set('setPosition');
      });
    }
  };

  /**
   * Slick Colorbox utility functions.
   *
   * @namespace
   */
  Drupal.slickColorbox = Drupal.slickColorbox || {

    /**
     * Provides common Slick Browser utilities.
     *
     * @name set
     *
     * @param {string} method
     *   The method to apply to .slick__slider element.
     */
    set: function (method) {
      var $box = $.colorbox.element();
      var $slider = $box.closest('.slick__slider');
      var $wrap = $slider.closest('.slick-wrapper');
      var curr;

      if ($slider.length) {
        curr = $box.closest('.slick__slide:not(.slick-cloned)').data('slickIndex');

        // Slick is broken after colorbox close, do setPosition manually.
        if (method === 'setPosition') {
          if ($wrap.length) {
            var $thumb = $wrap.find('.slick--thumbnail .slick__slider');
            $thumb.slick('slickGoTo', curr);
          }
          $slider.slick('slickGoTo', curr);
        }
        else {
          $slider.slick(method);
        }
      }
    }
  };

}(jQuery, Drupal));
