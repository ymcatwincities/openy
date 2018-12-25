(function ($) {

    /**
     * Attaches the plugin - Farbtastic.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior.
     */
    Drupal.behaviors.attachFarbtastic = {
        attach: function (context, settings) {
            var farb = $.farbtastic("#color-picker-container");
            jQuery('.color-preview', context).on('focusin', function() {
                farb.linkTo(this);
            });
        }
    };


})(jQuery);
