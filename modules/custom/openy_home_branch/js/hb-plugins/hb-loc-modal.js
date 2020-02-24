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
    attach: function (settings) {
      // Attach plugin instance to hb-loc-modal.
      // @see openy_home_branch/js/hb-plugin-base.js
      $('#hb-loc-modal').hbPlugin(settings);
    },
    settings: {
      selector: null,
      event: null,
      element: null,
      listSelector: '#hb-locations-list',
      btnYesSelector: '.action-save',
      btnNoSelector: '.close, .action-cancel',
      modalTitle: drupalSettings.home_branch.hb_loc_modal.modalTitle,
      modalDescription: drupalSettings.home_branch.hb_loc_modal.modalDescription,
      dontAskTitle: drupalSettings.home_branch.hb_loc_modal.dontAskTitle,
      learnMoreText: drupalSettings.home_branch.hb_loc_modal.learnMoreText,
      delay: drupalSettings.home_branch.hb_loc_modal.modalDelay,
      init: function () {
        if (!this.element) {
          return;
        }

        var selected = Drupal.homeBranch.getValue('id');
        var $locationList = this.element.find('#hb-locations-list');
        if (selected) {
          $locationList.val(selected);
        }
      },
      appendOptions: function () {
        var locations = Drupal.homeBranch.getLocations();
        var $locationList = this.element.find('#hb-locations-list');
        for (var locationId in locations) {
          if (!locations.hasOwnProperty(locationId)) {
            continue;
          }
          var locationName = locations[locationId];
          $('<option value="' + locationId + '">' + locationName + '</option>').appendTo($locationList);
        }
      },
      handleDontAsk: function () {
        var $dontAskCheckbox = this.element.find('#hb-dont-ask-checkbox');
        $dontAskCheckbox.attr('checked', Drupal.homeBranch.getValue('dontAsk'));
        $dontAskCheckbox.on('click', function () {
          Drupal.homeBranch.setValue('dontAsk', $(this).is(':checked'));
        });
      },
      bindButtons: function () {
        var self = this;
        this.element.find(self.btnYesSelector).on('click', function () {
          var $locationList = self.element.find(self.listSelector);
          var value = $locationList.val();
          Drupal.homeBranch.setValue('id', value === 'null' ? null : value);
          self.hide();
        });
        this.element.find(self.btnNoSelector).on('click', function () {
          self.hide();
        });
        this.element.find('.open-learn-more').on('click', function () {
          self.element.addClass('modal-content__learn_more');
        });
        this.element.find('.close-learn-more').on('click', function () {
          self.element.removeClass('modal-content__learn_more');
        });
        this.element.find('#hb-locations-list').on('change', function () {
          // Set don't ask checkbox on location select.
          self.element.find('#hb-dont-ask-checkbox').prop('checked', true);
        });
      },
      hide: function () {
        this.element.addClass('hidden');
      },
      show: function (forceShow) {
        var timestamp = Math.floor(Date.now() / 1000);
        var lastShowTime = Drupal.homeBranch.getValue('lastShowTime');
        var allowToShow = timestamp > lastShowTime + this.delay;
        if (this.areCookiesEnabled() && (allowToShow || forceShow)) {
          this.element.removeClass('hidden');
          Drupal.homeBranch.setValue('lastShowTime', timestamp);
        }
      },
      addMarkup: function (context) {
        // Save created element in plugin.
        this.element = $('<div id="hb-loc-modal" class="hidden">' +
          '<div class="hb-loc-modal__modal" tabindex="-1" role="dialog">' +
            '<div class="modal-content">' +
              '<div class="hb-loc-modal__modal--header">' +
                '<h4><strong>' + this.modalTitle + '</strong></h4>' +
                '<button type="button" class="close"><span aria-hidden="true">&times;</span></button>' +
              '</div>' +
              '<div class="hb-loc-modal__modal--body">' +
                '<div>' +
                  this.modalDescription + '<br>' +
                  '<button type="button" class="btn btn-link open-learn-more">Learn more</button>' +
                '</div>' +
                '<div class="form">' +
                  '<select id="hb-locations-list" required class="form-select form-control">' +
                    '<option value="null" selected>Select location</option>' +
                  '</select>' +
                  '<div class="dont-ask hb-checkbox-wrapper">' +
                    '<input type="checkbox" id="hb-dont-ask-checkbox">' +
                    '<label for="hb-dont-ask-checkbox">' + this.dontAskTitle + '</label>' +
                  '</div>' +
                '</div>' +
              '</div>' +
              '<div class="hb-loc-modal__modal--footer">' +
                '<button class="btn btn-lg btn-default action-cancel">No</button>' +
                '<button class="btn btn-lg btn-success action-save">Yes</button>' +
              '</div>' +
              '<div class="hb-loc-modal__modal--learn-more">' +
                '<div class="learn-more-title">' +
                    '<h4 class="learn-more-title--label">' + this.modalTitle + '</h4>' +
                    '<button type="button" class="btn btn-link close-learn-more">Close</button>' +
                '</div>' +
                '<div class="learn-more-content">' + this.learnMoreText + '</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>');
        $('body').append(this.element);
        this.appendOptions();
        this.handleDontAsk();
        this.bindButtons();

        // Let HomeBranch know how to call the modal window.
        var self = this;
        Drupal.homeBranch.showModal = function (force) {
          self.show(force);
        };
      },
      // Check that cookies enabled in browser.
      areCookiesEnabled: function () {
        try {
          document.cookie = 'cookietest=1';
          var cookiesEnabled = document.cookie.indexOf('cookietest=') !== -1;
          document.cookie = 'cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT';
          return cookiesEnabled;
        }
        catch (e) {
          return false;
        }
      },
    }
  });

})(jQuery, Drupal);
