/**
 * @file
 * Location finder extension with Home Branch logic.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Add plugin, that related to HB Location modal form.
   */
  Drupal.homeBranch.plugins.push({
    name: 'hb-loc-modal',
    attach: (context) => {
      // Attach plugin instance to hb-loc-modal.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('#hb-loc-modal', context).hbPlugin({
        selector: 'a.hb-loc-modal',
        event: 'click',
        element: null,
        init: function () {
          console.log('INIT modal');
          if (!this.element) {
            return;
          }
          // TODO: investigate why on first load selected not detected.
          let selected = Drupal.homeBranch.getValue('id');
          let locations = Drupal.homeBranch.getLocations();
        },
        onChange: function (event, el) {
          // Show HB locations modal.
          $(document).trigger('hb-modal-show');
        },
        addMarkup: function (context) {
          $('body', context).append(`
            <div id="hb-loc-modal">
              <div class="hb-loc-modal__modal" tabindex="-1" role="dialog">
                <div class="modal-content">
                  <div class="hb-loc-modal__modal--header">
                    <h4><strong>Home branch</strong></h4>
                    <button type="button" class="close"><span aria-hidden="true">&times;</span></button>
                  </div>
                  <div class="hb-loc-modal__modal--body">
                    <div>Would you like to set a different location as your 'home branch'?<br><a>Learn more</a></div>
                    <div class="form">
                      <select id="hb-locations-list" required class="form-select form-control">
                        <option value="null" disabled selected>Select location</option>
                      </select>
                      <div class="dont-ask">
                        <input type="checkbox" id="hb-dont-ask-checkbox">
                        <label for="hb-dont-ask-checkbox">Don't ask me again</label>
                      </div>
                    </div>
                  </div>
                  <div class="hb-loc-modal__modal--footer">
                    <button class="btn btn-lg btn-default action-cancel">No</button>
                    <button class="btn btn-lg btn-success action-save">Yes</button>
                  </div>
                </div>
              </div>
            </div>
          `);
          // Save created element in plugin.
          this.element = $('#hb-loc-modal');
        },
      });
    },
  });

})(jQuery, Drupal);
