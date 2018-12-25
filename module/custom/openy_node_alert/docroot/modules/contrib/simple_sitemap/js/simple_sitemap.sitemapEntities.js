/**
 * @file
 * Attaches simple_sitemap behaviors to the sitemap entities form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapSitemapEntities = {
    attach: function(context, settings) {
      var allEntities = settings.simple_sitemap.all_entities;
      var atomicEntities = settings.simple_sitemap.atomic_entities;

      // Hide the 'Regenerate sitemap' field to only display it if settings have changed.
      $('.form-item-simple-sitemap-regenerate-now').hide();

      $.each(allEntities, function(index, value) {

        // On load: hide all warning messages.
        $('#warning-' + value).hide();

        // On change: Show or hide warning message dependent on 'enabled' checkbox.
        var enabledId = '#edit-' + value + '-enabled';
        $(enabledId).change(function() {
          if ($(enabledId).is(':checked')) {
            $('#warning-' + value).hide();
          }
          else {
            $('#warning-' + value).show();
          }

          // Show 'Regenerate sitemap' field if 'enabled' setting has changed.
          $('.form-item-simple-sitemap-regenerate-now').show();
        });
      });

      // Show priority settings if atomic entity enabled on form load.
      $.each(atomicEntities, function(index, value) {
        var enabledId = '#edit-' + value + '-enabled';
        var priorityId = '.form-item-' + value + '-simple-sitemap-priority';

        // On load: Show or hide priority setting dependent on 'enabled' checkbox.
        if ($(enabledId).is(':checked')) {
          $(priorityId).show();
        }
        else {
          $(priorityId).hide();
        }

        // On change: Show or hide priority setting dependent on 'enabled' checkbox.
        $(enabledId).change(function() {
          if ($(enabledId).is(':checked')) {
            $(priorityId).show();
          }
          else {
            $(priorityId).hide();
          }
        });

        // Show 'Regenerate sitemap' field if 'priority' setting has changed.
        $(priorityId).change(function() {
          $('.form-item-simple-sitemap-regenerate-now').show();
        });
      });
    }
  };
})(jQuery);
