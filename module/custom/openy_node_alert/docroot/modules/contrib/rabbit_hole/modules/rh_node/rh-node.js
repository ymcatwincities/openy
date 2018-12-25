/**
 * @file
 */

(function($) {

  Drupal.behaviors.rhNode = {
    attach: function (context, settings) {

      // Set the summary for the settings form.
      $('fieldset.rabbit-hole-settings-form').drupalSetSummary(function() {
        var $rabbitHoleAction = $('.rabbit-hole-action-setting input:checked');

        // Get the label of the selected action.
        var summary = $('label[for=' + $rabbitHoleAction.attr('id') + ']').text();
        return Drupal.checkPlain(summary);
      });

    }
  }

})(jQuery);
