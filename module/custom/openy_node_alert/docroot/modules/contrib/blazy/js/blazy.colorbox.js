/**
 * @file
 */

(function ($, Drupal, drupalSettings, window) {

  'use strict';

  var cboxTimer;
  var $body = $('body');

  /**
   * Blazy Colorbox utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} box
   *   The colorbox HTML element.
   */
  function blazyColorbox(i, box) {
    var $box = $(box);
    var media = $box.data('media') || {};
    var isMedia = media.type !== 'image' ? true : false;
    var runtimeOptions = {
      rel: media.rel || null,
      iframe: isMedia,
      title: function () {
        var $caption = $box.next('.litebox-caption');
        return $caption.length ? $caption.html() : '';
      },
      onComplete: function () {
        removeClasses();
        $body.addClass('colorbox-on colorbox-on--' + media.type);

        if (isMedia) {
          resizeBox();
          $body.addClass('colorbox-on--media');
        }
      },
      onClosed: function () {
        removeClasses();
      }
    };

    /**
     * Remove the custom colorbox classes.
     */
    function removeClasses() {
      $body.removeClass(function (index, css) {
        return (css.match(/(^|\s)colorbox-\S+/g) || []).join(' ');
      });
    }

    /**
     * Resize the colorbox.
     */
    function resizeBox() {
      window.clearTimeout(cboxTimer);

      var o = {
        width: media.width || drupalSettings.colorbox.maxWidth,
        height: media.height || drupalSettings.colorbox.maxHeight
      };

      cboxTimer = window.setTimeout(function () {
        if ($('#cboxOverlay').is(':visible')) {
          var $container = $('#cboxLoadedContent');
          var $iframe = $('.cboxIframe', $container);

          if ($iframe.length) {
            $container.addClass('media media--ratio');
            $iframe.attr('width', o.width).attr('height', o.height).addClass('media__element');
            $container.css({paddingBottom: (o.height / o.width) * 100 + '%', height: 0});

            $.colorbox.resize({
              innerWidth: o.width,
              innerHeight: o.height
            });
          }
          else {
            $container.removeClass('media media--ratio');
            $container.css({paddingBottom: '', height: o.height}).removeClass('media__element');
          }
        }
      }, 10);
    }

    $box.colorbox($.extend({}, drupalSettings.colorbox, runtimeOptions));
  }

  /**
   * Attaches blazy colorbox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyColorbox = {
    attach: function (context) {
      if (drupalSettings.colorbox.mobiledetect && window.matchMedia) {
        // Disable Colorbox for small screens.
        var mq = window.matchMedia('(max-device-width: ' + drupalSettings.colorbox.mobiledevicewidth + ')');
        if (mq.matches) {
          return;
        }
      }

      $('[data-colorbox-trigger]', context).once('blazy-colorbox').each(blazyColorbox);
    }
  };

})(jQuery, Drupal, drupalSettings, this);
