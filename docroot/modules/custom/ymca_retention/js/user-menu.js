(function($) {

  Drupal.behaviors.ymca_retention_user_menu = {};
  Drupal.behaviors.ymca_retention_user_menu.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-user-menu-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-user-menu-processed');

    // Go back to Introduction after logout.
    $('.ysr-user-menu__logout').click(function () {
      if ($('.yfr-tabs').is(':visible')) {
        // Displaying about/intro tab.
        $('.yfr-tabs a[href="#about"]').tab('show');
      }
      else {
        // Expanding about/intro item.
        $('.yfr-accordion a[href="#about-collapse"]').removeClass('collapsed');
        $('.yfr-accordion #about-collapse').addClass('in');
      }
    });

    // Populate modal content depending on the clicked link.
    $('#ymca-retention-modal', context)
      .on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget),
          type = $button.data('type'),
          $modal = $(this),
          $modal_body = $modal.find('.modal-body');

        if ($button.is('a')) {
          var hash = $button[0].hash;
          if (hash) {
            // Setting tab ID on Log in and Register forms.
            $(':input[name="tab_id"]').val(hash.substring(1));

            // Updating Register link anchor on Log in form.
            $('.login-register-link').attr('href', hash);
          }
        }

        // Check for already existing content and save it.
        if ($.trim($modal_body.html())) {
          $modal_body.find('.ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
        }

        var title = 'Login';

        // Add requested form to the modal body.
        if (type === 'register') {
          title = 'Register';
          $('#ymca-retention-user-menu-register-form').appendTo($modal_body);
        }
        else if (type === 'login') {
          $('#ymca-retention-user-menu-login-form').appendTo($modal_body);
        }

        // Assign modal title.
        $modal.find('.modal-title').text(title);
      }).
      on('hidden.bs.modal', function (event) {
        // Save the form back.
        var $modal = $(this);
        $modal.find('.modal-body .ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
      });
  };

})(jQuery);
