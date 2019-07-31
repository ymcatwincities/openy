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
    attach: (context) => {
      // Attach plugin instance to header menu item.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('.hb-menu-selector', context).hbPlugin({
        selector: null,
        inputSelector: drupalSettings.home_branch.hb_loc_selector_pef.inputSelector,
        linkSelector: drupalSettings.home_branch.hb_loc_selector_pef.linkSelector,
        event: null,
        element: null,
        init: function () {
          let selected = Drupal.homeBranch.getValue('id');
          if (!selected) {
            return;
          }
          let locations = Drupal.homeBranch.getLocations();
          // Get url from paragraph's field.
          let url = $(this.linkSelector).attr('href');
          location.href = url + '/?locations=' + encodeURIComponent(locations[selected]);

          // That's just for the visual effect.
          $(this.inputSelector).find('input[value="' + locations[selected] + '"]', context).click();
        }
      })
    }
  });

})(jQuery, Drupal, drupalSettings);
