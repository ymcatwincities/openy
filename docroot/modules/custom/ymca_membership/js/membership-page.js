(function($) {
  $(document).ready(function() {
    if ( $('.membership-form-wrapper .errortext').length >0 ){
      $('.try-the-y-toggle').addClass('active');
      $('.membership-form-wrapper').show();
      $('.membership-form-wrapper .row').prepend($('section.messages').html());
      $('section.messages').remove();
      $('html, body').animate({
        scrollTop: $('.membership-form-wrapper').offset().top - 150
      }, 'fast');
    }

    $('.try-the-y-toggle').on('click touchend', function(e) {
      e.preventDefault();
      if (!$(this).hasClass('active')) {
        $(this).addClass('active');
        $('.membership-form-wrapper').slideDown();
        $('html, body').animate({
          scrollTop: $('.membership-form-wrapper').offset().top - $('.nav-global').height(),
        }, 'fast');
      }
      else {
        $(this).removeClass('active');
        $('.membership-form-wrapper').slideUp();
      }
    });

    $('.enrollment-fee').height($('.monthly-fee').height());

    $('.discover-link-wrap a').on('click touchend', function(e) {
      e.preventDefault();
      if (!$(this).hasClass('active')) {
        $(this).addClass('active');
        $(this).find('.glyphicon').removeClass('glyphicon-plus').addClass('glyphicon-minus');
        $('.discover-section').slideDown();
      }
      else {
        $(this).removeClass('active');
        $(this).find('.glyphicon').removeClass('glyphicon-minus').addClass('glyphicon-plus');
        $('.discover-section').slideUp();
      }
    });

    $('form.registration_block select option').each(function() {
      var location = window.location.hash.replace('#', '');
      if (location !== '' && $(this).text().toLowerCase().match(location)) {
        $(this).attr('selected', true);
        $('.try-the-y-toggle').trigger('click');
      }
    });
  });
})(jQuery);