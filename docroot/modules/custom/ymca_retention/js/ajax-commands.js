(function($, Drupal) {
  /**
   * Add new command for reading a message.
   */
  Drupal.AjaxCommands.prototype.ymcaRetentionModalHide = function(ajax, response, status) {
    $('#ymca-retention-modal').modal('hide');
  };
})(jQuery, Drupal);
