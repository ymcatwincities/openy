<?php
$GLOBALS['config']['custom/personify']['personify_url']  =  'https://ygtcprod2.personifycloud.com';
$GLOBALS['config']['custom/personify']['sso']             =  'sso';
$GLOBALS['config']['custom/personify']['wsdl_url']        =  $GLOBALS['config']['custom/personify']['personify_url'] . '/' . 
	                                                         $GLOBALS['config']['custom/personify']['sso'] .
                                                             '/webservice/service.asmx?WSDL';
$GLOBALS['config']['custom/personify']['advanced_search_url']
										=  $GLOBALS['config']['custom/personify']['personify_url'] .
									'/personifyebusiness/YMSP/AdvancedSearch/tabid/177/Default.aspx';

// Vendor settings 
$GLOBALS['config']['custom/personify']['vendorID']  =  '<YOUR VENDOR ID>';
$GLOBALS['config']['custom/personify']['vendorUsername']  =  '<YOUR VENDOR USERNAME>';
$GLOBALS['config']['custom/personify']['vendorPassword']  =  '<YOUR VENDOR PASSWORD>';
$GLOBALS['config']['custom/personify']['vendorBlock']     =  '<YOUR VENDOR BLOCK>';

// Governs SOAP requests:
ini_set( 'default_socket_timeout', 10 );

$GLOBALS['config']['custom/personify']['location_types'] = array( 
		array( 'name' => "YMCA Centers", 'tag' => "YMCA", ),
		array( 'name' => "Camps", 'tag' => "Camp", )
		);

// For advanced search
$GLOBALS['config']['custom/personify']['locations'] = array (
  array (
    'name' => 'Andover YMCA Community Center',
    'personify_brcode' => '32',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Blaisdell YMCA - South Minneapolis',
    'personify_brcode' => '14',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Camp du Nord',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp Icaghowan ',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp Ihduhapi',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp Menogyn',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp St. Croix',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp Warren',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Camp Widjiwagan',
    'personify_brcode' => '',
    'tags' =>
    array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Lino Lakes YMCA',
    'personify_brcode' => '81',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Minneapolis Downtown YMCA',
    'personify_brcode' => '17',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'St. Paul Eastside YMCA',
    'personify_brcode' => '76',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Elk River YMCA',
    'personify_brcode' => '34',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Heritage Park YMCA',
    'personify_brcode' => '18',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'North Community YMCA',
    'personify_brcode' => '16',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Emma B. Howe YMCA - Coon Rapids',
    'personify_brcode' => '27',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Hastings YMCA',
    'personify_brcode' => '85',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'St. Paul Midway YMCA',
    'personify_brcode' => '77',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Burnsville YMCA',
    'personify_brcode' => '30',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Shoreview YMCA',
    'personify_brcode' => '89',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'New Hope YMCA',
    'personify_brcode' => '24',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Ridgedale YMCA - Minnetonka',
    'personify_brcode' => '22',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'River Valley YMCA in Prior Lake',
    'personify_brcode' => '36',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'St. Paul Downtown YMCA',
    'personify_brcode' => '75',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'West St. Paul YMCA',
    'personify_brcode' => '70',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Southdale YMCA – Edina',
    'personify_brcode' => '20',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Woodbury YMCA',
    'personify_brcode' => '83',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Eagan YMCA',
    'personify_brcode' => '82',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Hudson, WI YMCA',
    'personify_brcode' => '84',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'White Bear Area YMCA',
    'personify_brcode' => '88',
    'tags' =>
    array (
      0 => 'YMCA',
    ),
  ),
  array (
    'name' => 'Day Camp Christmas Tree',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Guy Robinson',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Heritage',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Ihduhapi',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Kici Yapi',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Kumalya',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Manitou',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Spring Lake',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp DayCroix',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
  array (
    'name' => 'Day Camp Streefland',
    'personify_brcode' => '',
    'tags' =>     array (
      0 => 'Camp',
    ),
  ),
);


