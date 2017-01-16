(function($) {
    "use strict";

    Drupal.behaviors.scrollableList = {
      attach: function (context, settings) {
        $('.camp-menu-wrapper').once().each(function() {
          var $this = $(this),
              $list = $this.find('ul'),
              $items = $list.find('li'),
              listWidth = 0,
              listPadding = 40;

          setTimeout(function() {
            $items.each(function() {
              listWidth += $(this).outerWidth();
          });

          $list.css('width', listWidth + listPadding + "px");

          var scroll = new IScroll($this.find('.columns')[0], {
            scrollX: true,
            scrollY: false,
            momentum: false,
            snap: false,
            bounce: true,
            touch: true,
            eventPassthrough: true
          });

            // GRADIENT BEHAVIOUR SCRIPT.
            var obj = $('.camp-menu');
            var objWrap = document.querySelector('.columns-gradient');
            var sliderLength = listWidth - objWrap.offsetWidth + 40;
            var firstGap = 20;

            if (window.innerWidth > 768) {
              sliderLength = listWidth - objWrap.offsetWidth + 150;
              firstGap = 60;
            }

            obj.get(0).addEventListener('touchmove', function() {
              var transformMatrix = obj.css("-webkit-transform") ||
                  obj.css("-moz-transform")    ||
                  obj.css("-ms-transform")     ||
                  obj.css("-o-transform")      ||
                  obj.css("transform");
              var matrix = transformMatrix.replace(/[^0-9\-.,]/g, '').split(',');
              var x = matrix[12] || matrix[4];
              var y = matrix[13] || matrix[5];
              console.log(x, y);
              if (x <= -sliderLength + listPadding) {
                objWrap.classList.remove('gradient-right');
              } else {
                objWrap.classList.add('gradient-right');
              }

              if (x >= -firstGap) {
                objWrap.classList.remove('gradient-left');
              } else {
                objWrap.classList.add('gradient-left');
              }
            });
        }, 100);
      });
    }
  };

})(jQuery);
