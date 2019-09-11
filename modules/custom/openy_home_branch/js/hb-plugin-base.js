/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  $.fn.hbPlugin = function (options) {

    this.settings = $.extend({

      // Selector that related to event (on-change, on-click).
      selector: null,

      // Event that trigger onChange function.
      event: null,

      // Storage for created element.
      element: null,

      // This function should contain base logic related to plugin instance.
      init: function (event, el) {},

      // This function should contain change logic related to plugin instance.
      onChange: function () {},

      // This function should provide markup related to plugin instance.
      addMarkup: function (context) {},

    }, options);

    var self = this;

    // Re-init plugin instance on storage update.
    $(document).on('hb-after-storage-update', function (event, data) {
      self.settings.init(self);
    });

    // Run settings.onChange function when settings.event fires.
    if (this.settings.event && this.settings.selector) {
      $(document).on(this.settings.event, this.settings.selector, function (event, el) {
        self.settings.onChange(event, this);
        return false;
      });
    }

    // Add Markup provided by plugin instance.
    self.settings.addMarkup(self);
    self.settings.init(self);

    return this;
  };

})(jQuery, Drupal, drupalSettings);
