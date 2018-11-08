/**
 * @file
 * Open Y Node Event JS.
 */

(function ($) {
  "use strict";

  // Set column height as max column info height
  Drupal.behaviors.setMaxEventInfoHeight = {
    attach: function (context, settings) {
      $('.node--type-event .field-event-location').each(function(context){
        var maxHeight = 0;
        var column = $(this).find('.info-col');
        column.each(function(){
          if ($(this).height() > maxHeight) {
            maxHeight = $(this).height();
          }
        });
        column.height(maxHeight);
      });
    }
  };

})(jQuery);
