/**
 * @file
 * Faq paragraph behaviour.
 */
(function ($) {
  Drupal.behaviors.openyFaqParagraph = {
    attach: function( context ) {
      $( context ).find( '.paragraph--type--faq-item' ).once( 'paragraphFaq' ).each( function () {
        // Q/A wrapper.
        var wrapper = $( this );
        // Question click event.
        $( '.field-question', wrapper ).on( 'click', function() {
          $( '.field-answer', wrapper ).toggle(200);

          if (wrapper.hasClass('conceal')) {
            wrapper.removeClass('conceal').addClass('show');
          }
          else {
            wrapper.removeClass('show').addClass('conceal');
          }
        });

      });
    }
  };

})(jQuery);
