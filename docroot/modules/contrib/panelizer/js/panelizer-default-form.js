/**
 * @file
 * Javascript for the Panelizer defaults page.
 */
(function ($) {
  Drupal.behaviors.panelizer_default_form = {
    attach: function (context, settings) {
      var $panelizer_checkbox = $(':input[name="panelizer[enable]"]');

      function update_form() {
        var $core_form = $('#field-display-overview-wrapper');
        if ($panelizer_checkbox.is(':checked')) {
          $core_form.fadeOut();
        }
        else {
          $core_form.fadeIn();
        }
      }

      $panelizer_checkbox.once('panelizer-default-form').click(update_form);
      update_form();
    }
  };
})(jQuery);
