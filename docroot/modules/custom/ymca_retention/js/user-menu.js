(function($) {

  Drupal.behaviors.ymca_retention_user_menu = {};
  Drupal.behaviors.ymca_retention_menu_forms = {};
  Drupal.behaviors.ymca_retention_user_menu.attach = function (context, settings) {
    if ($('body').hasClass('ymca-retention-user-menu-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-user-menu-processed');

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

        var title = Drupal.t('Sign In');

        // Add requested form to the modal body.
        if (type === 'login') {
          $('#ymca-retention-user-menu-login-form').appendTo($modal_body);
        }
        else if (type === 'register') {
          title = Drupal.t('Registration');
          $('#ymca-retention-user-menu-register-form').appendTo($modal_body);
        }
        else if (type === 'email') {
          title = Drupal.t('Change email');
          $('#ymca-retention-user-email-change-form').appendTo($modal_body);
        }

        // Assign modal title.
        $modal.find('.modal-title').text(Drupal.t(title));
      }).
      on('hidden.bs.modal', function (event) {
        // Save the form back.
        var $modal = $(this);
        $refresh_button_selector = '#' + $modal.find('.modal-body .ymca-retention-modal-form').attr('id') + " .refresh";
        $modal.find('.modal-body .ymca-retention-modal-form').appendTo($('.ymca-retention-user-menu-forms'));
        // Hide "Where can I find my Member ID?" info.
        $('.compain-facility-access-hint-wrapper').removeClass('in').addClass('collapse');
        // Refresh form.
        $( $refresh_button_selector ).click();
      });

    $('#bonus-modal-day', context)
      .on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget),
          type = $button.data('type'),
          $modal = $(this),
          $modal_body = $modal.find('.modal-body');

        // Add requested form to the modal body.
        if (type === 'tabs-lock') {
          $('#ymca-retention-user-menu-tabs-lock').appendTo($modal_body);
            var title = $modal_body.find('.days-left').text();
            // Assign modal title.
            $modal.find('.modal-title').text(Drupal.t(title));
        }
      });
  };

  Drupal.behaviors.ymca_retention_menu_forms.attach = function (context, settings) {
    $('input[name=membership_id], input[name=email]', $('.ymca-retention-login-form, .ymca-retention-register-form', context)).on('keyup', function (event) {
      if (event.keyCode != 13) {
        return true;
      }
      $(this).parents('form').find('input.form-submit').trigger('mousedown');
    });
    var isIE = /(MSIE|Trident\/|Edge\/)/i.test(navigator.userAgent);
    if (isIE) {
      $(document).on('keyup', 'form', function (event) {
        if (event.keyCode == 13) {
          $(this).find("[type=submit]").trigger("mousedown");
        }
      });
    }
  };

})(jQuery);
