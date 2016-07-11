/**
 * @file dropzonejs_eb_widget.common.js
 *
 * Bundles various dropzone eb widget behaviours.
 */
(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.dropzonejsEbWidgetCommon = {
    attach: function(context) {
      if (typeof drupalSettings.dropzonejs.instances !== "undefined") {
        _.each(drupalSettings.dropzonejs.instances, function (item) {
          var $form = $(item.instance.element).parents('form');

          if ($form.hasClass("dropzonejs-disable-submit")) {
            var $submit = $form.find('.is-entity-browser-submit');
            $submit.prop("disabled", true);

            item.instance.on("queuecomplete", function () {
              if (item.instance.getRejectedFiles().length == 0) {
                $submit.prop("disabled", false);
              }
              else {
                $submit.prop("disabled", true);
              }
            });

            item.instance.on("removedfile", function (file) {
              if (item.instance.getRejectedFiles().length == 0) {
                $submit.removeAttr("disabled");
              }
            });
          }
        });
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
