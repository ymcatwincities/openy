(function($) {

  Drupal.behaviors.ymca_retention_introduction = {
    attach: function (context, settings) {
      $('.compain-info-block .content-text', context)
        .once('content-text').matchHeight({
          byRow: true,
          property: 'height',
          target: null,
          remove: false
        });
    }
  };

})(jQuery);
