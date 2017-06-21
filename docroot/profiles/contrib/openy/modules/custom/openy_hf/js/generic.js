/**
 * @file
 * HF Javascript routines.
 */

/**
 * @file generic.js
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  /**
   * Registers behaviors related to headerless-footerless architecture.
   */
  Drupal.behaviors.openy_hf = {
    attach: function (context) {
      var cookie_name = 'openy_fh_dnr';
      var cookie = $.cookie(cookie_name);
      var query = getUrlVars();

      // Quickly render the page if there is no query & cookies without cookies being set.
      if (query.dnr === undefined && cookie === undefined) {
        doFullRender();
        return;
      }

      // Reset any settings if we 'reset' parameter.
      if (query.dnr == 'reset') {
        doFullRender();
        $.removeCookie(cookie_name);
        return;
      }

      // Render by cookie setting.
      if (cookie !== undefined) {
        doPartialRender(cookie);
        return;
      }

      // Eventually, render by params and set the cookie.
      doPartialRender(query.dnr);

      /**
       * Do render header & footer.
       */
      function doFullRender() {
        renderFooter();
        renderHeader();
      }

      /**
       * Controls the render of header & footer.
       *
       * @param {string} param
       *   Param. Example: fh, h f.
       */
      function doPartialRender(param) {
        switch (param) {
          case 'hf':
            setCookie(param);
            return;
          case 'h':
            setCookie(param);
            renderFooter();
            break;
          case 'f':
            setCookie(param);
            renderHeader();
            break;
          default:
            renderFooter();
            renderHeader();
        }
      }

      /**
       * Set the cookie if it is not set.
       *
       * @param {string} param
       *   Parameter. Example: hf, f, h.
       */
      function setCookie(param) {
        if (cookie === undefined) {
          var date = new Date();
          date.setTime(date.getTime() + (drupalSettings['openy_hf.cookieLifeTime'] * 1000));
          $.cookie(cookie_name, param, { expires: date });
        }
      }

      function renderHeader() {
        $.each(drupalSettings['openy_hf.header_replacements'], function (index, value) {
          $(context).find(value.selector).attr("style", "visibility:visible;display:inherit;");
        });
      }

      function renderFooter() {
        $.each(drupalSettings['openy_hf.footer_replacements'], function (index, value) {
          $(context).find(value.selector).attr("style", "visibility:visible;display:inherit;");
        });
      }

      function getUrlVars() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (var i = 0; i < hashes.length; i++) {
          hash = hashes[i].split('=');
          vars.push(hash[0]);
          vars[hash[0]] = hash[1];
        }
        return vars;
      }


    }
  };

}(jQuery, Drupal, drupalSettings));
