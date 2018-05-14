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

  });
})(jQuery);

Drupal.behaviors.z_ymca_membership_page = {
  attach: function (context, settings) {
    if (jQuery('.page_membership, .page_membership_new', context).length) {
      // Expand form if there were errors during the form validation.
      if (jQuery('form .error', context).length) {
        // Wrap drupal error messages and move to appropriate place.
        jQuery("div[role=alert]")
          .wrap('<div class="inline-messages alert-error alert-dismissable"></div>')
          .parent()
          .prependTo(jQuery('.inline-messages-placeholder'));
        // Add required classes for theming.
        jQuery('form .error', context).each(function () {
          jQuery(this).addClass('errortext');
        });
        jQuery(window).scrollTop(jQuery('h2').first().offset().top-120);
      }
      // if on membership page & there is a hash, expand and preselect
      if (window.location.hash) {
        // pre-select form dropdown
        if (settings.webform_mapping[window.location.hash] !== undefined) {
          jQuery(window).scrollTop(jQuery('h2').first().offset().top-120);
          setTimeout(function () {
            jQuery('.try-the-y-toggle').trigger('click');
          }, 0);

          jQuery(".contact-message-membership-form-form select").val(settings.webform_mapping[window.location.hash]);
        }
      }
    }
  }
};
