(function($) {

  /**
   * Handles tabs to accordion conversion.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.ymca_retention_tabs_selector = {};
  Drupal.behaviors.ymca_retention_tabs_selector.attach = function (context, settings) {
    $('.yfr-tabs', context)
      .once('tab-collapse')
      .tabCollapse({
        tabsClass: 'hidden-xs',
        accordionClass: 'visible-xs yfr-accordion'
      });
  };

})(jQuery);
