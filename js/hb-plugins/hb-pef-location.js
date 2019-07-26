/**
 * @file
 * Home branch PEF extension.
 */

(function ($, Drupal) {

  /**
   * Init home branch PEF location selector on load.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.openyHomeBranchPEFLocationSelector = {
    attach(context, settings) {
      if (typeof Drupal.homeBranch === 'undefined') {
        return;
      }

      var selected = Drupal.homeBranch.getValue('id');
      if (selected) {
        var locations = Drupal.homeBranch.getLocations();
        // Get url from paragraph's field.
        var url = $('.field-prgf-repeat-lschedules-prf a').attr('href');
        location.href = url + '/?locations=' + locations[selected];
      }
    }
  };

})(jQuery, Drupal);
