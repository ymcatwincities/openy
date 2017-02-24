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
    $('.compain-tabs', context)
      .once('tab-collapse')
      .tabCollapse({
        tabsClass: 'hidden-xs',
        accordionClass: 'visible-xs compain-accordion',

      });

    $(document).on('show.bs.collapse', '.panel-collapse, a[data-toggle="collapse"]', function (event) {
      // Get accordion item.
      var $target = $(event.currentTarget);

      // Checking whether private content is available.
      if ($target.find('.login-required.ng-hide').length === 0) {
        return;
      }

      // Getting accordion item ID.
      var tab_id = $target.attr('id').replace('-collapse', '');

      // Collapsing accordion item.
      $('a[href="#' + tab_id + '-collapse"]').addClass('collapsed');

      // Displaying login form on modal.
      $link = $('.nav-tabs a[href="#' + tab_id + '"]').click();
      $('#ymca-retention-modal .modal-header').show();

      event.preventDefault();
    });

    // Scroll to just opened tab.
    $(document).on('shown.bs.collapse', function (event) {
      $('body').animate({
        scrollTop: $(this.activeElement).offset().top
      });
    });
  };

})(jQuery);
