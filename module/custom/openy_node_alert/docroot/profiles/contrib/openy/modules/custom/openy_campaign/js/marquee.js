/**
 * @file
 * Apply jQuery.marquee to draw Campaign Winners stream.
 */

(function ($, Drupal, drupalSettings) {

  $(function() {
    $('.marquee').marquee();
    $('#campaign-winner-stream').find('.close').on('click', function(event) {
      $('#campaign-winner-stream').remove();
    });
  });

})(jQuery, Drupal, drupalSettings);
