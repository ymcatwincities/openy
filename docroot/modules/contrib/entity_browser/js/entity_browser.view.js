/**
 * @file entity_browser.view.js
 *
 * Defines the behavior of the entity browser's view widget.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.entityBrowserView = {
    attach: function (context) {
      // Add the AJAX to exposed forms.
      // We do this as the selector in core/modules/views/js/ajax_view.js
      // assumes that the form the exposed filters reside in has a
      // views-related ID, which ours does not.
      var views_instance = Drupal.views.instances[Object.keys(Drupal.views.instances)[0]];
      if (views_instance) {
        // Initialize the exposed form AJAX.
        views_instance.$exposed_form = $('div#views-exposed-form-' + views_instance.settings.view_name.replace(/_/g, '-') + '-' + views_instance.settings.view_display_id.replace(/_/g, '-'));
        views_instance.$exposed_form.once('exposed-form').each(jQuery.proxy(views_instance.attachExposedFormAjax, views_instance));

        // The form values form_id, form_token, and form_build_id will break
        // the exposed form. Remove them by splicing the end of form_values.
        if (views_instance.exposedFormAjax && views_instance.exposedFormAjax.length > 0) {
          var ajax = views_instance.exposedFormAjax[0];
          ajax.options.beforeSubmit = function (form_values, element_settings, options) {
            form_values = form_values.splice(form_values.length - 3, 3);
            ajax.ajaxing = true;
            return ajax.beforeSubmit(form_values, element_settings, options);
          };
        }
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
