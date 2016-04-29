/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 *
 * @todo: Tidy up.
 */
(function($) {

  "use strict";

  // Hide the 'Regenerate sitemap' field to only display it if settings have changed.
  $('.form-item-simple-sitemap-regenerate-now').hide();

  Drupal.behaviors.simple_sitemapForm = {
    attach: function(context) {
        if ($(context).find('#edit-simple-sitemap-index-content-1').is(':checked')) {
          // Show 'Priority' field if 'Index sitemap' is ticked.
          $('.form-item-simple-sitemap-priority').show();
        }
        else {  // Hide 'Priority' field if 'Index sitemap' is unticked.
          $('.form-item-simple-sitemap-priority').hide();
        }

        // Show 'Regenerate sitemap' field if setting has changed.
        $( "#edit-simple-sitemap-index-content" ).change(function() {
          $('.form-item-simple-sitemap-regenerate-now').show();
          if ($(context).find('#edit-simple-sitemap-index-content-1').is(':checked')) {
            // Show 'Priority' field if 'Index sitemap' is ticked.
            $('.form-item-simple-sitemap-priority').show();
          }
          else {  // Hide 'Priority' field if 'Index sitemap' is unticked.
            $('.form-item-simple-sitemap-priority').hide();
          }
        });

        // Show 'Regenerate sitemap' field if setting has changed.
        $( "#edit-simple-sitemap-priority" ).change(function() {
          $('.form-item-simple-sitemap-regenerate-now').show();
        });
    }
  };
})(jQuery);
