<?php
$GLOBALS['config']['custom/groupex']['groupex_account_id']  =  3;
$GLOBALS['config']['custom/groupex']['groupex_notification_emails']  =  "jfable@mac.com,jessica@clockwork.net";
$GLOBALS['config']['custom/groupex']['groupex_notification_email_from']  =  "support@clockwork.net";

$GLOBALS['config']['custom/groupex']['groupex_print_url']    =  $GLOBALS['config']['custom/groupex']['groupex_url']
                                                           . '/ymcatwincities/print.php';

$GLOBALS['config']['custom/groupex']['use_cache']  =  true;
// Override GroupExPro API url
$GLOBALS['config']['custom/groupex']['groupex_api_url']   =  'http://api.groupexpro.com';
$GLOBALS['config']['custom/groupex']['groupex_json_url']  =  $GLOBALS['config']['custom/groupex']['groupex_api_url'] . '/schedule/embed/';
