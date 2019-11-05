/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * Branch Locations (Download PDF)
   */
  Drupal.behaviors.openyPdfDownload = {
    attach: function (context, settings) {
      var pdfBtnContainer = $('.openy_carnation .groupex-pdf-link-container');
      if (pdfBtnContainer.length) {
        pdfBtnContainer.find('a').html('PDF <i class="fas fa-download"></i>');
        pdfBtnContainer.insertAfter('.groupex-form-full .form-submit');
      }
    }
  };
})(jQuery);
