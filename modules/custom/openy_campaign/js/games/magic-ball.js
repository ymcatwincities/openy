/**
 * @file
 * Instant games: magic ball.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openyCampaignMagicBall = {
    attach: function (context) {
        $('.eball').once('magic-ball').mouseenter(function(){
          $('.textbox').html(drupalSettings.openy_campaign.result);
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
