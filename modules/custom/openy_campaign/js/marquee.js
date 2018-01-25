(function ($, Drupal, drupalSettings) {

  $(function() {
    $('.marquee').marquee();
    $('#campaign-winner-stream').find('.close').click(function(event) {
      $('#campaign-winner-stream').remove();
    });
  });

})(jQuery, Drupal, drupalSettings);
