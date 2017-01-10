/**
 * @file
 * Attaches administration-specific behavior for the Sitemap module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Displays the ordering options of sitemap content items on the admin page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors to the filter admin view.
   */
  Drupal.behaviors.sitemapContent = {
    attach: function (context, settings) {
      var $context = $(context);
      $context.find('#edit-sitemap-content input.form-checkbox').once('sitemap-status').each(function () {
        var $checkbox = $(this);
        // Retrieve the tabledrag row belonging to this sitemap content item.
        var $row = $context.find('#' + $checkbox.attr('data-drupal-selector').replace('show', 'order')).closest('tr');

        // Bind click handler to this checkbox to conditionally show and hide
        // the sitemap content items's tableDrag row.
        $checkbox.on('click.sitemapContentUpdate', function () {
          if ($checkbox.is(':checked')) {
            $row.show();
          }
          else {
            $row.hide();
          }
          // Restripe table after toggling visibility of table row.
          Drupal.tableDrag['sitemap-order'].restripeTable();
        });

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.sitemapContentUpdate');
      });
    }
  };

})(jQuery, Drupal);
