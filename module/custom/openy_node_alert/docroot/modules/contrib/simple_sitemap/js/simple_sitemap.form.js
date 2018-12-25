/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapForm = {
    attach: function(context) {

      // On load: Hide the 'Regenerate sitemap' field to only display it if settings have changed.
      $('.form-item-simple-sitemap-regenerate-now').hide();

      // On load: Show or hide 'priority' setting dependant on 'enabled' setting.
      if ($('#edit-simple-sitemap-index-content-1').is(':checked')) {
        $('.form-item-simple-sitemap-priority').show();
      }
      else {
        $('.form-item-simple-sitemap-priority').hide();
      }

      // On change: Show or hide 'priority' setting dependant on 'enabled' setting.
      $("#edit-simple-sitemap-index-content").change(function() {
        if ($('#edit-simple-sitemap-index-content-1').is(':checked')) {
          $('.form-item-simple-sitemap-priority').show();
        }
        else {
          $('.form-item-simple-sitemap-priority').hide();
        }
        // Show 'Regenerate sitemap' field if 'enabled' setting has changed.
        $('.form-item-simple-sitemap-regenerate-now').show();
      });

      // Show 'Regenerate sitemap' field if 'priority' setting has changed.
      $("#edit-simple-sitemap-priority").change(function() {
        $('.form-item-simple-sitemap-regenerate-now').show();
      });
    }
  };
})(jQuery);
