/**
 * @file
 * jQuery to provide summary information inside vertical tabs.
 */

(function ($) {

  "use strict";

  /**
   * Provide summary information for vertical tabs.
   */
  Drupal.behaviors.scheduler_settings = {
    attach: function (context) {

      // Provide summary when editing a node.
      $('fieldset#edit-scheduler-settings', context).drupalSetSummary(function (context) {
        var vals = [];
        if ($('#edit-publish-on').val()) {
          vals.push(Drupal.t('Scheduled for publishing'));
        }
        if ($('#edit-unpublish-on').val()) {
          vals.push(Drupal.t('Scheduled for unpublishing'));
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not scheduled'));
        }
        return vals.join('<br/>');
      });

      // Provide summary during content type configuration.
      $('#edit-scheduler', context).drupalSetSummary(function (context) {
        var vals = [];
        if ($('#edit-scheduler-publish-enable', context).is(':checked')) {
          vals.push(Drupal.t('Publishing enabled'));
        }
        if ($('#edit-scheduler-unpublish-enable', context).is(':checked')) {
          vals.push(Drupal.t('Unpublishing enabled'));
        }
        return vals.join('<br/>');
      });
    }
  };

})(jQuery);
