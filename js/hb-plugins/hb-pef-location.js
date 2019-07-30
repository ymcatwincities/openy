/**
 * @file
 * Home branch PEF extension.
 */

(function ($, Drupal) {

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
        inputSelector: '.paragraph--type--repeat-schedules-loc',
        linkSelector: '.field-prgf-repeat-lschedules-prf a',
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

})(jQuery, Drupal);
