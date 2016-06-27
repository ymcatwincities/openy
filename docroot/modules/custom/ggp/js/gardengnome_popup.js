(function($){
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
  $(window).load(function(){
    var $el = $('.gardengnome-player');
    var $preview = $el.find('.gardengnome-player-preview');
    var $window = $(window);
    var dim = {
      width: $preview.naturalWidth(),
      height: $preview.naturalHeight()
    };
    window.resizeBy(dim.width - $window.width(), dim.height - $window.height());
    $el.gardengnomePlayer({width: $preview.naturalWidth(), height: $preview.naturalHeight()});
  });
}(jQuery));
