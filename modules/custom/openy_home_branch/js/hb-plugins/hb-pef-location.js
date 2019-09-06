/**
 * @file
 * Home branch PEF extension.
 */

(function ($, Drupal, drupalSettings) {

  // By default hide locations list.
  $(drupalSettings.home_branch.hb_loc_selector_pef.locationsWrapper).hide();
  $(drupalSettings.home_branch.hb_loc_selector_pef.locationsWrapper).after('<div style="height: 50px; margin: 30px;">' +
    '<svg class="spinner" viewBox="0 0 50 50" data-size="normal" data-flow="centered">' +
      '<circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5" stroke="#93bfec"></circle>' +
    '</svg></div>');

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
      locationsWrapper: drupalSettings.home_branch.hb_loc_selector_pef.locationsWrapper,
      inputSelector: drupalSettings.home_branch.hb_loc_selector_pef.inputSelector,
      linkSelector: drupalSettings.home_branch.hb_loc_selector_pef.linkSelector,
      event: null,
      element: null,
      init: function () {
        var selected = Drupal.homeBranch.getValue('id');
        if (!selected) {
          // Show locations list in case Home Branch not selected.
          $(this.locationsWrapper).show();
          $('.spinner').hide();
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
