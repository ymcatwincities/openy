/**
 * @file
 * Drupal behavior to run gardengnome player plugins.
 */
(function($, Drupal, drupalSettings) {

  // Helper to calculate natural width and height of an image.
  var props = ['Width', 'Height'], prop;
  while (prop = props.pop()) {
    (function (natural, prop) {
      $.fn[natural] = (natural in new Image()) ?
        function () {
          return this[0][natural];
        } :
        function () {
          var node = this[0];
          var img;
          var value;

          if (node.tagName.toLowerCase() === 'img') {
            img = new Image();
            img.src = node.src;
            value = img[prop];
          }
          return value;
        };
    }('natural' + prop, prop.toLowerCase()));
  }

  /**
   * Controls Gardengnome player setup.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.gardengnome_player = {
    attach: function(context, settings) {
      // Trigger Popups
      $('.gardengnome-player', context).once('gardengnome-player').each(function(){
        var $el = $(this);
        if ($el.data('display') === 'popup') {
          // Don't event attach the player for popup windows.
          $el.click(function(event) {
            var width = parseInt($(this).attr('data-popup-width'), 10);
            var height = parseInt($(this).attr('data-popup-height'), 10);
            var params = [
              'toolbar=0',
              'scrollbars=0',
              'location=0',
              'status=0',
              'menubar=0',
              'width=' + width,
              'height=' + height
            ].join(',');
            var file = $el.data('package').split('/').pop();
            var popup = window.open(drupalSettings.path.baseUrl + 'gardengnome-player-popup?package=' + file, 'Gardengnome Player', params);
            popup.moveTo(Math.floor(screen.width/2 - width/2), Math.floor(screen.height/2 - height/2));
            event.preventDefault();
          });
        }
        else {
          // Default options.
          var options = {
            width: 400,
            height: 300
          };
          // Attach the player to inline displays.
          var $preview = $el.find('.gardengnome-player-preview');
          if ($preview.size()) {
            $preview.load(function() {
              options = {
                width: $preview.naturalWidth(),
                height: $preview.naturalHeight()
              };
              $el.gardengnomePlayer(options);
            });
          }
          else {
            console.log('no image');
            $el.gardengnomePlayer(options);
          }
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));