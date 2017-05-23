(function($) {
  "use strict";

  Drupal.behaviors.openy_branch_selector = {
    nid: 0,
    action: 'flag',
    attach: function(context, settings) {
      if (!$('.branch-cookie-handler', context).length) {
        return;
      }

      var found = settings.path.currentPath.match(/node\/(\d+)/i);
      this.nid = found[1];

      var self = this;
      $('.branch-cookie-handler', context)
        .once()
        .click(function(e) {
          if (self.action == 'flag') {
            $.cookie('openy_preferred_branch', self.nid, { expires: 365, path: '/' });
          }
          else {
            $.removeCookie('openy_preferred_branch', { path: '/' });
          }
          self.updateLink(context, settings);
          e.preventDefault();
        });

      Drupal.behaviors.openy_branch_selector.updateLink(context, settings);
    },

    updateLink: function(context, settings) {
      var preferred_branch = $.cookie('openy_preferred_branch');
      var link_text = Drupal.t('Save as preferred branch');
      this.action = 'flag';
      if (typeof preferred_branch !== 'undefined' && preferred_branch == this.nid) {
        this.action = 'unflag';
        link_text = Drupal.t('This is your preferred branch, remove as preferred branch');
      }

      // Update link text.
      $('.branch-cookie-handler', context).text(link_text);
    }
  };

})(jQuery);
