/**
 * @file dropzonejs_eb_widget.common.js
 *
 * Bundles various dropzone eb widget behaviours.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsPostIntegrationEbWidgetEditJs = {
    attach: function(context) {
      if (typeof drupalSettings.dropzonejs.instances !== "undefined") {
        _.each(drupalSettings.dropzonejs.instances, function (item) {
          var $form = $(item.instance.element).parents('form');

          if ($form.hasClass("dropzonejs-disable-submit")) {
            var $submit = $form.find('.is-entity-browser-submit');
            $submit.prop("disabled", false);

            item.instance.on("queuecomplete", function () {
              var $form = this;
              $('#edit-edit', $form).trigger('mousedown');
            }.bind($form));
          }
        });
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
