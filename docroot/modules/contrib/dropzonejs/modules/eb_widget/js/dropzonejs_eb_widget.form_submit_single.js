/**
 * @file dropzonejs_eb_widget.form_submit_single.js
 *
 * Simple, easy solving out multiple form submission problem.
 * We redirect all form submissions to the jquery.form-submit-single handler.
 * This in turn will make sure that no multiple submissions of the same form
 * will take place.
 * N.B.: This can/should probably be placed in a different place.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsFormSubmitSingle = {
    attach: function(context) {
      $('body').on('submit.formSubmitSingle', 'form:not([method~="GET"])', $.onFormSubmitSingle);
    }
  };

}(jQuery, Drupal, drupalSettings));
