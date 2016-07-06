/**
 * @file generic.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviors related to blocks.
   */
  Drupal.behaviors.aB = {
    attach: function (context) {
      $.each(drupalSettings['ab'], function (index, value) {
        $(context).find(value.selector).once('ab').each(function () {
          $(this).replaceWith(value.html);
        });
      });
    }
  };
  
}(jQuery, Drupal, drupalSettings));
