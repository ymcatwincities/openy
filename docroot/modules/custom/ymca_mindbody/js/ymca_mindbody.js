(function($) {

  Drupal.behaviors.ymca_mindbody = {
    attach: function (context, settings) {
      var data = {};
      $('.mindbody-products-list a').on('click', function() {
        data.session_length = $(this).data('session');
        data.package = $(this).data('package');
      });
      $('#modal').on('shown.bs.modal', function() {
        $('.personify_location_list a').on('click', function(e) {
          e.preventDefault();
          var product_code = $(this).data('id')+ '_PT_' + data.package + '_SESS_' + data.session_length + '_MIN';
          window.location.href = settings.personify_product_url + settings.products_codes[product_code];
        });
      });
      $('#mindbody-pt-form-wrapper .form-item-mb-location input').each(function() {
        if ($('#mindbody-pt-form-wrapper .form-item-mb-program').length === 0) {
          $(this).attr('checked', false);
        }
      });
      // Change training location, length, type.
      $('#block-mainpagecontent .header-row a.change').once('change').on('click', function () {
        var target = $(this).attr('href');
        if ($(target).length !== 0) {
          $(target).slideToggle();
        }
      });
    }
  };

})(jQuery);
