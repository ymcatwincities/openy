function init() {
  var site_id = '2';

  // call init_template if it exists in the theme.
  if ( window.init_template ) {
    init_template();
  }

  if( window.rewrite_preview_hrefs ) {
    rewrite_preview_hrefs( site_id );
  }

  if( window.add_preview_control_panel ) {
    add_preview_control_panel( );
  }

}
