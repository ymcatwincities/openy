/**
 * @file
 */

(function ($) {

  Drupal.behaviors.google_search = {
    attach: function (context, settings) {
      // Only run this script on full documents, not ajax requests.
      if (context !== document) {
        return;
      }
      var gcse = document.createElement('script');
      gcse.type = 'text/javascript';
      gcse.async = true;
      gcse.src = 'https://cse.google.com/cse.js?cx=' + settings.engine_id;
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(gcse, s);
    }
  };

}(jQuery));
