/**
 * @file
 * Home branch PEF extension.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Adds plugin related to PEF Schedules locations paragraph.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-pef-location',
    attach: function (settings) {
      // Attach plugin instance to header menu item.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.hb-menu-selector').hbPlugin(settings);
    },
    settings: {
      selector: null,
      inputSelector: drupalSettings.home_branch.hb_loc_selector_pef.inputSelector,
      linkSelector: drupalSettings.home_branch.hb_loc_selector_pef.linkSelector,
      event: null,
      element: null,
      init: function () {
        var selected = Drupal.homeBranch.getValue('id');
        if (!selected) {
          return;
        }
        var locations = Drupal.homeBranch.getLocations();
        // Get url from paragraph's field.
        var url = $(this.linkSelector).attr('href');
        location.href = url + '/?locations=' + encodeURIComponent(locations[selected].replace('&', '%26'));

        // That's just for the visual effect.
        $(this.inputSelector).find('input[value="' + locations[selected] + '"]').click();
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
