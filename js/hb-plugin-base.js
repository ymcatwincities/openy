/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  $.fn.hbPlugin = function (options) {

    this.settings = $.extend({

      // Selector that related to event (on-change, on-click).
      selector: '.some-selector',

      // Event that trigger onChange function.
      event: 'change',

      // Storage for created element.
      element: null,

      // This function should contain base logic related to plugin instance.
      init: function (event, el) {},

      // This function should contain change logic related to plugin instance.
      onChange: function () {},

      // This function should provide markup related to plugin instance.
      addMarkup: function (context) {},

    }, options);


    let self = this;

    // Re-init plugin instance on storage update.
    $(document).on('hb-after-storage-update', function (event, data) {
      self.settings.init();
    });

    // Run settings.onChange function on settings.event.
    // Note: unbind() used for prevent events firing multiple times.
    $(this.settings.selector).unbind().on(this.settings.event, function (event) {
      // TODO: looks like on-click event for hb-menu-selector.js not work here.
      self.settings.onChange(event, this);
    });

    // Add Markup provided by plugin instance.
    self.settings.addMarkup();
    self.settings.init();

    return this;
  };

  /**
   * Init Home Branch plugins on the page.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranch = {
    attach(context, settings) {
      $('body', context).once('hb-plugin').each(function () {
        if (typeof Drupal.homeBranch.plugins !== 'undefined' && Drupal.homeBranch.plugins.length > 0) {
          Drupal.homeBranch.plugins.forEach(function (plugin, key, arr, context) {
            plugin.attach(context);
          });
        }
      });

      // TODO: temp solution.
      $('a.hb-menu-selector').unbind().on('click', function (event) {
        $(document).trigger('hb-modal-show');
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
