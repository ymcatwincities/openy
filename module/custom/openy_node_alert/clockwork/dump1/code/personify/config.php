<?php

$GLOBALS['product_abbr']  =  'custom/personify';

/* file locations */
$GLOBALS['config']['custom/personify']['product_template_directory']  =  $GLOBALS['www_template_directory'].'/custom/personify';

/* Tables */
$GLOBALS['config']['custom/personify']['internal_nav_table']  =  '$product_name_internal_nav';

$GLOBALS['config']['custom/personify']['connection_params']  =  array( 'connection_timeout' => 10,
															           'proxy_host' => $GLOBALS['http_proxy_host'],
															           'proxy_port' => $GLOBALS['http_proxy_port'] );


$wsdl_cache_directory = $GLOBALS['base_directory'] . "/data/tmp/wsdl_cache_" . $_SERVER['SERVER_ADDR']; 
_ensure_directory_exists( $wsdl_cache_directory );
ini_set( 'soap.wsdl_cache_dir', $wsdl_cache_directory );


/* Required Libs for load_required_libraries() */
$GLOBALS['required_libraries']['custom/personify']  =  array( );

HelpfulRoutines::include_local_config( __DIR__ );
