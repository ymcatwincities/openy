/**
 * @file
 * Provides bLazy loader.
 */

(function (Drupal, drupalSettings, _db, window, document) {

  'use strict';

  /**
   * Blazy public methods.
   *
   * @namespace
   */
  Drupal.blazy = Drupal.blazy || {
    init: null,
    windowWidth: 0,
    done: false,
    globals: function () {
      var me = this;
      var settings = drupalSettings.blazy || {};
      var commons = {
        success: me.clearing,
        error: me.clearing
      };

      return _db.extend(settings, commons);
    },

    clearing: function (el) {
      // The .b-lazy element can be attached to IMG, or DIV as CSS background.
      el.className = el.className.replace(/(\S+)loading/, '');

      // The .is-loading can be .grid, .slide__content, .box__content, etc.
      var loaders = [
        _db.closest(el, '.is-loading'),
        _db.closest(el, '[class*="loading"]')
      ];

      // Also cleans up closest containers containing loading class.
      _db.forEach(loaders, function (wrapEl) {
        if (wrapEl !== null) {
          wrapEl.className = wrapEl.className.replace(/(\S+)loading/, '');
        }
      });
    }
  };

  /**
   * Blazy utility functions.
   *
   * @param {HTMLElement} elm
   *   The Blazy HTML element.
   */
  function doBlazy(elm) {
    var me = Drupal.blazy;
    var dataAttr = elm.getAttribute('data-blazy');
    var empty = dataAttr === '' || dataAttr === '[]';
    var data = empty ? false : _db.parse(dataAttr);
    var opts = !data ? me.globals() : _db.extend({}, me.globals(), data);
    var ratios = elm.querySelectorAll('[data-dimensions]');
    var loopRatio = ratios.length > 0;

    /**
     * Updates the dynamic multi-breakpoint aspect ratio.
     *
     * This only applies to multi-serving images with aspect ratio fluid if
     * each element contains [data-dimensions] attribute.
     * Static single aspect ratio, e.g. `media--ratio--169`, will be ignored,
     * and will use CSS instead.
     *
     * @param {HTMLElement} el
     *   The .media--ratio HTML element.
     */
    function updateRatio(el) {
      var dimensions = !el.getAttribute('data-dimensions') ? false : _db.parse(el.getAttribute('data-dimensions'));

      if (!dimensions) {
        return;
      }

      var keys = Object.keys(dimensions);
      var xs = keys[0];
      var xl = keys[keys.length - 1];
      var mw = function (w) {
        return w >= me.windowWidth;
      };
      var pad = keys.filter(mw).map(function (v) {
        return dimensions[v];
      }).shift();

      if (pad === 'undefined') {
        pad = dimensions[me.windowWidth >= xl ? xl : xs];
      }

      if (pad !== 'undefined') {
        el.style.paddingBottom = pad + '%';
      }
    }

    // Initializes Blazy instance.
    me.init = new Blazy(opts);

    // Reacts on resizing.
    if (!me.done) {
      _db.resize(function () {
        me.windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

        if (loopRatio) {
          _db.forEach(ratios, updateRatio, elm);
        }

        // Dispatch resizing event.
        _db.trigger(elm, 'resizing', {windowWidth: me.windowWidth});
      })();

      me.done = true;
    }

    elm.className += ' blazy--on';
  }

  /**
   * Attaches blazy behavior to HTML element identified by [data-blazy].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazy = {
    attach: function (context) {
      var me = Drupal.blazy;
      var el = context.querySelector('[data-blazy]');

      // Runs basic Blazy if no [data-blazy] found, probably a single image.
      // Cannot use .contains(), as IE11 doesn't support method 'contains'.
      if (el === null) {
        me.init = new Blazy(me.globals());
        return;
      }

      // Runs Blazy with multi-serving images, and aspect ratio supports.
      var blazies = context.querySelectorAll('.blazy:not(.blazy--on)');
      _db.once(_db.forEach(blazies, doBlazy));
    }
  };

}(Drupal, drupalSettings, dBlazy, this, this.document));
