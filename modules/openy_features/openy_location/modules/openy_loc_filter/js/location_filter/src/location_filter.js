(function($) {
  "use strict";

   /**
   * Makes all checkboxes checked or unchecked when the state of the checkbox 'All' is changed.
   */
   Drupal.behaviors.openy_location_filter = {
    attach: function(context, settings) {
      $('#edit-locations-all', context).on('change', function() {
        var checkboxAll = this;
        $('input[id^=edit-locations-]', context).each(function() {
          if (this.id !== checkboxAll.id) {
            $(this).prop('checked', $(checkboxAll).is(':checked'));
          }
        });
      });
    }
  };
})(jQuery);
