/**
 * @file
 * Expander Block behaviors.
 */

(function ($) {

  "use strict";

  /**
   * Collapse or expand block.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior collapsing or expanding block contents.
   */
  Drupal.behaviors.expanderBlock = {
    attach: function(context, settings) {
      $('.expander-block h4', context).on('click', function() {
        $(this).parent().toggleClass('collapsed expanded');
      });
    }
  };

})(jQuery);
