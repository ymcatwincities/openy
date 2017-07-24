/**
 * @file auto_submit.js
 *
 * Provides a "form auto-submit" feature for the Better Exposed Filters module.
 */

(function ($, Drupal) {

  /**
   * To make a form auto submit, all you have to do is 3 things:
   *
   * Use the "better_exposed_filters/auto_submit" js library.
   *
   * On gadgets you want to auto-submit when changed, add the
   * data-bef-auto-submit attribute. With FAPI, add:
   * @code
   *  '#attributes' => array('data-bef-auto-submit' => ''),
   * @endcode
   *
   * If you want to have auto-submit for every form element, add the
   * data-bef-auto-submit-full-form to the form. With FAPI, add:
   * @code
   *   '#attributes' => array('data-bef-auto-submit-full-form' => ''),
   * @endcode
   *
   * If you want to exclude a field from the bef-auto-submit-full-form auto
   * submission, add an attribute of data-bef-auto-submit-exclude to the form
   * element. With FAPI, add:
   * @code
   *   '#attributes' => array('data-bef-auto-submit-exclude' => ''),
   * @endcode
   *
   * Finally, you have to identify which button you want clicked for autosubmit.
   * The behavior of this button will be honored if it's ajaxy or not:
   * @code
   *  '#attributes' => array('data-bef-auto-submit-click' => ''),
   * @endcode
   *
   * Currently only 'select', 'radio', 'checkbox' and 'textfield' types are
   * supported. We probably could use additional support for HTML5 input types.
   */
  Drupal.behaviors.betterExposedFiltersAutoSubmit = {
    attach: function(context) {
      // 'this' references the form element.
      function triggerSubmit (e) {
        $(this).find('[data-bef-auto-submit-click]').click();
      }

      // The change event bubbles so we only need to bind it to the outer form.
      $('form[data-bef-auto-submit-full-form]', context)
        .add('[data-bef-auto-submit]', context)
        .filter('form, select, input:not(:text, :submit)')
        .once('bef-auto-submit')
        .change(function (e) {
          // don't trigger on text change for full-form
          if ($(e.target).is(':not(:text, :submit, [data-bef-auto-submit-exclude])')) {
            triggerSubmit.call(e.target.form);
          }
        });

      // e.keyCode: key
      var discardKeyCode = [
        16, // shift
        17, // ctrl
        18, // alt
        20, // caps lock
        33, // page up
        34, // page down
        35, // end
        36, // home
        37, // left arrow
        38, // up arrow
        39, // right arrow
        40, // down arrow
         9, // tab
        13, // enter
        27  // esc
      ];
      // Don't wait for change event on textfields.
      $('[data-bef-auto-submit-full-form] input:text, input:text[data-bef-auto-submit]', context)
        .filter(':not([data-bef-auto-submit-exclude])')
        .once('bef-auto-submit', function () {
          // each textinput element has his own timeout
          var timeoutID = 0;
          $(this)
            .bind('keydown keyup', function (e) {
              if ($.inArray(e.keyCode, discardKeyCode) === -1) {
                timeoutID && clearTimeout(timeoutID);
              }
            })
            .keyup(function(e) {
              if ($.inArray(e.keyCode, discardKeyCode) === -1) {
                timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 500);
              }
            })
            .bind('change', function (e) {
              if ($.inArray(e.keyCode, discardKeyCode) === -1) {
                timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 500);
              }
            });
        });
    }
  }

}(jQuery, Drupal));
