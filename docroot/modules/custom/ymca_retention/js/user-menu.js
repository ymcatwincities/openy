(function($) {

  Drupal.behaviors.ymca_retention_user_menu = {};
  Drupal.behaviors.ymca_retention_user_menu.attach = function (context, settings) {
    // Drupal.ymca_retention.app;

    $('#ymca-retention-modal', context).once('ymca-retention-user-menu')
      .on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget),
          type = $button.data('type'),
          $modal = $(this),
          $modal_body = $modal.find('.modal-body');

        // Assign modal title.
        $modal.find('.modal-title').text($button.text());

        // Check for already existing content and save it.
        if ($.trim($modal_body.html())) {
          $modal_body.find('.ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
        }

        // Add requested form to the modal body.
        if (type === 'register') {
          $('#ymca-retention-user-menu-register-form').appendTo($modal_body);
        }
        else if (type === 'login') {
          $('#ymca-retention-user-menu-login-form').appendTo($modal_body);
        }
      }).
      on('hidden.bs.modal', function (event) {
        var $modal = $(this);
        // Save the form back.
        $modal.find('.modal-body .ysr-user-menu__form').appendTo($('.ysr-user-menu__forms'));
      });
  };

})(jQuery);
