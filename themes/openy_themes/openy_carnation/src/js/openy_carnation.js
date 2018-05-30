/**
 * @file
 * OpenY Carnation JS.
 */

(function ($) {
  "use strict";
  Drupal.openy_carnation = Drupal.openy_carnation || {};

  // Sidebar collapsible.
  Drupal.behaviors.openy_carnation_init = {
    attach: function (context, settings) {

      // homepage banner
      $('.layout-container').once('banner-zone').each(function() {
        $('.layout-container').prepend('<div class="banner-zone-node"></div>');
        $('.wrapper-field-header-content .paragraph--type--banner').appendTo('.banner-zone-node');

        if ($('.banner-zone-node').text().length > 0 ) {
          $('body').addClass('with-banner');
        }

        if ($('.banner-zone-node').text().length <= 0 ) {
          $('body').addClass('without-banner');
        }
      });

      $('.webform-submission-form').addClass('container');

      if($(".field-link-attribute:contains('New Window')").length) {
        $('.field-prgf-clm-link a').attr('target', '_blank');
      }

      // menus
      // $(".row-level-3").hide();

      $( ".row-level-2 li" ).mouseover(function() {
        var show_div = this.className;
        if(show_div.match(/menu-item(.*)/g)){
          var parent_class = show_div.match(/menu-item(.*)/g);
          var level3_class =  parent_class[0].split(" ")[0];
          $("."+level3_class+" .row-level-3").show();
        }
      });

      $( ".row-level-2 li" ).mouseout(function() {
        var show_div = this.className;
        if(show_div.match(/menu-item(.*)/g)){
          var parent_class = show_div.match(/menu-item(.*)/g);
          var level3_class =  parent_class[0].split(" ")[0];
          $("."+level3_class+" .row-level-3").hide();
        }
      });

    }
  };

})(jQuery);
