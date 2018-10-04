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
          var answer = $( '.field-answer', wrapper );

          if ( answer.is( ':hidden') ) {
            answer.slideDown( 200 );
          }
          else {
            answer.slideUp( 200 );
          }
        });

      });
    }
  };

})(jQuery);
