/**
 * @file file_browser.view.js
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */

  Drupal.behaviors.FileBrowserView = {
    attach: function (context) {
      var $view = $('.grid-item').parent();
      $view.prepend('<div class="grid-sizer"></div><div class="gutter-sizer"></div>').once();

      // Indicate that images are loading.
      $view.append('<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>');
      $view.imagesLoaded(function () {
        // Save the scroll position.
        var scroll = document.body.scrollTop;
        // Remove old Masonry object if it exists. This allows modules like
        // Views Infinite Scroll to function with File Browser.
        if ($view.data('masonry')) {
          $view.masonry('destroy');
        }
        $view.masonry({
          columnWidth: '.grid-sizer',
          gutter: '.gutter-sizer',
          itemSelector: '.grid-item',
          percentPosition: true,
          isFitWidth:true
        });
        // Jump to the old scroll position.
        document.body.scrollTop = scroll;
        // Add a class to reveal the loaded images, which avoids FOUC.
        $('.grid-item').addClass('item-style');
        $view.find('.ajax-progress').remove();
      });

      // Adjusts the body padding to account for out fixed actions bar.
      function adjustBodyPadding() {
        setTimeout(function () {
          $('body').css('padding-bottom', $('.file-browser-actions').outerHeight() + 'px');
        }, 2000);
      }

      // Indicate when files have been selected.
      var $entities = $(context).find('.entities-list');
      Drupal.file_browser = Drupal.file_browser || {
          fileCounter: {}
        };

      function renderFileCounter() {
        $('.file-browser-file-counter').each(function () {
          $(this).remove();
        });
        for (var id in Drupal.file_browser.fileCounter) {
          var count = Drupal.file_browser.fileCounter[id];
          if (count > 0) {
            var text = Drupal.formatPlural(count, 'Selected one time', 'Selected @count times');
            var $counter = $('<div class="file-browser-file-counter"></div>').text(text);
            $('[name="entity_browser_select[file:' + id + ']"]').closest('.grid-item').find('.grid-item-info').prepend($counter);
          }
        }
      }

      $entities.once('file-browser-register-add-entities')
        .bind('add-entities', function (event, entity_ids) {
          adjustBodyPadding();
          for (var i in entity_ids) {
            var id = entity_ids[i].split(':')[1];
            if (!Drupal.file_browser.fileCounter[id]) {
              Drupal.file_browser.fileCounter[id] = 0;
            }
            Drupal.file_browser.fileCounter[id]++;
          }
          renderFileCounter();
        });

      $entities.once('file-browser-register-remove-entities')
        .bind('remove-entities', function (event, entity_ids) {
          adjustBodyPadding();
          for (var i in entity_ids) {
            var id = entity_ids[i].split('_')[1];
            if (!Drupal.file_browser.fileCounter[id]) {
              Drupal.file_browser.fileCounter[id] = 0;
            }
            else {
              Drupal.file_browser.fileCounter[id]--;
            }
            renderFileCounter();
          }
        });
    }
  };

}(jQuery, Drupal));
