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

  Drupal.behaviors.atcFix = {
    attach: function (context, settings) {
      $('.addtocalendar').click(function() {
        if ($(this).hasClass('activated')) {
          $(this).removeClass('activated');
          $(this).find('.atcb-list').css({
            'display' : 'none',
            'visibility' : 'hidden'
          });
        }
        else {
          $(this).addClass('activated');
          $(this).find('.atcb-list').css({
            'display' : 'block',
            'visibility' : 'visible'
          });
        }
      });
    }
  };

})(jQuery);
