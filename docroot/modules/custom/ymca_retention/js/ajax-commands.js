(function($, Drupal) {
  /**
   * Add new command for reading a message.
   */
  Drupal.AjaxCommands.prototype.ymcaRetentionModalHide = function(ajax, response, status) {
    $('#ymca-retention-modal').modal('hide');
  };
  Drupal.AjaxCommands.prototype.ymcaRetentionSetTab = function(ajax, response, status) {
    var tab_id = response.arguments.tabId;

    if ($('.yfr-tabs').is(':visible')) {
      var $link = $('.yfr-tabs a[href="#' + tab_id + '"]');

      // Displaying selected tab after login.
      $link.tab('show');
      $link.parent().addClass('active');
    }
    else {
      // Expanding selected accordion item after login.
      $('.yfr-accordion a[href="#' + tab_id + '-collapse"]').removeClass('collapsed');
      $('.yfr-accordion #' + tab_id + '-collapse').addClass('in').css('height', 'auto');
    }

  };
})(jQuery, Drupal);
