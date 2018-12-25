<?php

$GLOBALS['product_abbr']  =  'custom/groupex';

/* file locations */
$GLOBALS['config']['custom/groupex']['product_template_directory']  =  $GLOBALS['www_template_directory'].'/custom/groupex';

/* Tables */
$GLOBALS['config']['custom/groupex']['internal_nav_table']  =  '$product_name_internal_nav';

$GLOBALS['config']['custom/groupex']['groupex_timeout']     =  10;   // Number of seconds before timeout
$GLOBALS['config']['custom/groupex']['groupex_notification_email_from']  =  "support@clockwork.net";


/* Required Libs for load_required_libraries() */
$GLOBALS['required_libraries']['custom/groupex']  =  array( );

// API URLs
$GLOBALS['config']['custom/groupex']['groupex_url']          =  'http://www.groupexpro.com';

$GLOBALS['config']['custom/groupex']['groupex_json_url']     =  $GLOBALS['config']['custom/groupex']['groupex_url']
	                                                       . '/schedule/embed/';
$GLOBALS['config']['custom/groupex']['groupex_print_url']    =  $GLOBALS['config']['custom/groupex']['groupex_url']
	                                                       . '/schedule/print.php';
$GLOBALS['config']['custom/groupex']['groupex_desc_url']     =  $GLOBALS['config']['custom/groupex']['groupex_url']
														   . '/schedule/descriptions.php';

// ADD THESE TO LOCAL CONFIG FILE
// Specify the GroupExPro account_id for the organization
// $GLOBALS['config']['custom/groupex']['groupex_account_id']   =  ACCOUNT_ID;
// Comma separated list of e-mail addresses to notify upon network error (timeout, etc)
// $GLOBALS['config']['custom/groupex']['groupex_notification_emails']  =  EMAILS;

HelpfulRoutines::include_local_config( __DIR__ );
