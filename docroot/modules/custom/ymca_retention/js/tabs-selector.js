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
    if ($('body').hasClass('ymca-retention-tabs-selector-processed')) {
      return;
    }
    $('body').addClass('ymca-retention-tabs-selector-processed');

    $('.compain-tabs', context)
      .once('tab-collapse')
      .tabCollapse({
        tabsClass: 'hidden-xs',
        accordionClass: 'visible-xs compain-accordion'
      });

    $(document).on('show.bs.collapse', '.panel-collapse, a[data-toggle="tab"]', function (event) {
      // Get accordion item.
      var $target = $(event.currentTarget);

      // Getting accordion item ID.
      var tab_id = $target.attr('id').replace('-collapse', '');

      // Collapsing accordion item.
      $('a[href="#' + tab_id + '-collapse"]').removeClass('collapsed');

      if (settings.ymca_retention.tabs_selector.campaign_started) {
        // Checking whether private content is available.
        if ($target.find('.login-required.ng-hide').length === 0) {
          return;
        }

        // Displaying login form on modal.
        $('.nav-tabs a[href="#' + tab_id + '"].login-required').click();
      }
      else {
        if (!$('.nav-tabs a[href="#' + tab_id + '"]').hasClass('login-required')) {
          return;
        }

        // Displaying campaign not started message on modal.
        $('.nav-tabs a[href="#' + tab_id + '"].campaign-not-started').click();
      }

      event.preventDefault();
    });

    $(document).on('hidden.bs.collapse', '.panel-collapse, a[data-toggle="tab"]', function (event) {
      // Get accordion item.
      var $target = $(event.currentTarget);

      // Getting accordion item ID.
      var tab_id = $target.attr('id').replace('-collapse', '');

      $('a[href="#' + tab_id + '-collapse"]').addClass('collapsed');
    });

    // Scroll to just opened tab.
    $(document).on('shown.bs.collapse', function (event) {
      $('body').animate({
        scrollTop: ($(event.target).offset().top-72)
      });
    });
    $('[data-dismiss="modal"]').click(function () {
      if (history.pushState) {
        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=tab_1';
        window.history.pushState({path: newurl}, '', newurl);
      }
    });
  };

})(jQuery);
