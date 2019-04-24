/**
 * @file
 * Instant games: flip cards.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  $('.flip-cards .card').on('click', function(e) {
    e.preventDefault();
    $(this).toggleClass('flipped');
    $('.flip-cards .card').off('click');
  });

})(jQuery, Drupal, drupalSettings);
