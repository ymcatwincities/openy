/**
 * jQuery Form Submit Single Plugin 1.0.0
 * https://github.com/sun/jquery-form-submit-single
 *
 * @version 1.0.0
 * @copyright 2013 Daniel F. Kudwien
 * @license MIT http://sun.mit-license.org/2013
 */

;(function (factory) {
  "use strict";
  if (typeof exports === 'object') {
    factory(require('jquery'));
  }
  else if (typeof define === 'function' && define.amd) {
    define(['jquery'], factory);
  }
  else {
    factory(jQuery);
  }
}(function ($) {
"use strict";

$.extend({
  /**
   * Prevents consecutive form submissions of identical form values.
   *
   * Repetitive form submissions that would submit the identical form values are
   * prevented, unless the form values are different to the previously submitted
   * values.
   *
   * @param event
   *   The submit event that triggered a form submit.
   */
  onFormSubmitSingle: function (event) {
    if (event.isDefaultPrevented()) {
      return;
    }
    var $form = $(event.currentTarget);
    var currentValues = $form.serialize();
    var previousValues = $form.attr('data-form-submit-single-last');
    if (previousValues === currentValues) {
      event.preventDefault();
    }
    else {
      $form.attr('data-form-submit-single-last', currentValues);
    }
  }

});

}));
