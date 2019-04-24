/**
 * @file
 * Profile presets and configuration UI.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Handler for tooltips between profiles.
   */
  Drupal.behaviors.openy_profile_preset = {
    attach: function (context, settings) {

      $(context).tooltip({
        items: ".tooltip-helper > a",
        classes: {
          "ui-tooltip": "openy-tooltip"
        },
        content: function() {
          var $element = $(this);
          return $element.parent().find('.tooltip-helper-contents').html();
        }
      });

      $(".package > a", context).on('click', function (e) {
        e.preventDefault();
        var $element = $(this);
        if (!$element.data('dialog')) {
          var $dialog = $element
              .parent()
              .find('.package-description')
              .dialog({
                autoOpen: false,
                modal: true,
                height: "auto",
                classes: {
                  "ui-dialog": "dialog-package",
                  "ui-dialog-titlebar": "dialog-package-titlebar",
                  "ui-dialog-title": "dialog-package-title",
                  "ui-dialog-titlebar-close": "dialog-package-titlebar-close",
                  "ui-dialog-content": "dialog-package-content"
                },
                resizable: false,
                title: $element.text()
              });
          $element.data('dialog', $dialog);
        }
        $element.data('dialog').dialog('open');
        if ($(window).width() > 480) {
          $element.data('dialog').dialog('option', 'width', 480);
        }
      });
    }
  };

  /**
   * Handler for tooltips between profiles.
   */
  Drupal.behaviors.iframe_wrapper = {
    attach: function (context, settings) {
      $("iframe", context).once('iframe-wrapper').each(function() {
        var height = $(this).height();
        var width = $(this).width();
        var $wrapper = $(this).wrap("<div class='iframe-wrapper'></div>");
        $wrapper.css('padding-bottom', 100 * height / width + '%');
      });
    }

  };

})(jQuery, Drupal, drupalSettings);
