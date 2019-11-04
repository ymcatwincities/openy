/**
 * @file
 * Javascript functionality for the openy focal point preview page.
 */

(function($, Drupal) {
  'use strict';

  Drupal.AjaxCommands.prototype.rerenderThumbnail = function (ajax, response, status) {
    var $image = $(response.selector);
    console.log(response.selector, 'rerenderThumbnail');
    if ($image.length) {
      var src = $image.attr('src') + '1';
      $image.attr('src', src);
    }
  };

})(jQuery, Drupal);
