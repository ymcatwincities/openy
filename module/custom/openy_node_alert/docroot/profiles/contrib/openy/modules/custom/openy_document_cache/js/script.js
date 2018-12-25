/**
 * @file
 * Main JS script.
 */

/**
 * @file generic.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Adds timestamp to document links to prevent caching.
   */
  Drupal.behaviors.openDocumentCache = {
    attach: function (context) {
      var timestamp = + new Date();
      $('a', context).once().each(function () {
        var link = $(this);
        var href = link.attr('href');
        if (href !== undefined) {
          if (href.match(/\.(pdf)/i)) {
            var param = href.indexOf('?') === -1 ? '?openyts=' : '&openyts=';
            link.attr("href", href + param + timestamp);
          }
        }
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
