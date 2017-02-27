(function($) {

  Drupal.behaviors.ymca_retention_user_menu = {};
  Drupal.behaviors.ymca_retention_menu_forms = {};
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
        // Reseting accordion.
        $('.yfr-accordion .collapse.in').removeClass('in');
        $('.yfr-accordion .panel-heading a').addClass('collapsed');

        // Expanding about/intro item.
        $('.yfr-accordion a[href="#about-collapse"]').removeClass('collapsed');
        $('.yfr-accordion #about-collapse').addClass('in').css('height', 'auto');
      }
    });

    // Populate modal content depending on the clicked link.
    $('#ymca-retention-modal', context)
      .on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget),
          type = $button.data('type'),
          $modal = $(this),
          $modal_body = $modal.find('.modal-body');

        // Reseting Register button anchor on Log in form.
        $('.login-register-link').attr('href', '#');

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
          $modal_body.find('.ymca-retention-modal-form').appendTo($('.ymca-retention-user-menu-forms'));
        }

        var title = Drupal.t('Login');

        // Add requested form to the modal body.
        if (type === 'login') {
          $('#ymca-retention-user-menu-login-form').appendTo($modal_body);
        }
        else if (type === 'register') {
          title = Drupal.t('Sign Up');
          $('#ymca-retention-user-menu-register-form').appendTo($modal_body);
        }
        else if (type === 'email') {
          title = Drupal.t('Change email');
          $('#ymca-retention-user-email-change-form').appendTo($modal_body);
        }
        else if (type === 'tabs-lock') {
          $modal.find('.modal-header').hide();
          $('#ymca-retention-user-menu-tabs-lock').appendTo($modal_body);
        }

        // Assign modal title.
        $modal.find('.modal-title').text(Drupal.t(title));
      }).
      on('hidden.bs.modal', function (event) {
        // Save the form back.
        var $modal = $(this);
        $refresh_button_selector = '#' + $modal.find('.modal-body .ymca-retention-modal-form').attr('id') + " .refresh";
        $modal.find('.modal-body .ymca-retention-modal-form').appendTo($('.ymca-retention-user-menu-forms'));
        // Restore the modal header.
        $modal.find('.modal-header').show();
        // Refresh form.
        $( $refresh_button_selector ).click();
      });
  };

  Drupal.behaviors.ymca_retention_menu_forms.attach = function (context, settings) {
    $('input[name=membership_id], input[name=email]', $('.ymca-retention-login-form, .ymca-retention-register-form', context)).on('keyup', function (event) {
      if (event.keyCode != 13) {
        return true;
      }
      $(this).parents('form').find('input.form-submit').trigger('mousedown');
    });
  };

})(jQuery);
