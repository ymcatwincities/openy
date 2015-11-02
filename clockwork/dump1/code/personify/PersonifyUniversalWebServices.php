<?php

load_section( 'custom/personify' );

class PersonifyUniversalWebServices extends PersonifyWebServices {


	protected $token;


	public function authenticate ( ) {

		$params  =  array(
			'Login'      => $GLOBALS['config']['custom/personify']['ws_login'],
			'Password'   => $GLOBALS['config']['custom/personify']['ws_password'],
			'aOrgId'     => $GLOBALS['config']['custom/personify']['ws_org_id'],
			'aOrgUnitId' => $GLOBALS['config']['custom/personify']['ws_org_unit_id'],
			);

		$result  =  $this->client->CONN_Connect( $params );
		$this->token  =  $result->CONN_ConnectResult->Token;
	}


	
	/**
	 * $type may be "", "Meeting", "Inventory", "Membership", "Subscription"
	 *
	 * @author  Selah Ben-Haim <selah@clockwork.net>
	 *
	 * @param    type    $name
	 * @return   type
	**/
	public function search_by_product_code ( $code, $date_sort = 'Ascending', $type = "" ) {
		$filter[]  =  array( 'PropertyName' => 'ProductCode',
							 'PropertyValue' => $code,
							 'FilterOperator' => 'Contains' );

		$sort[]  =  array( 'PropertyName' => 'ProductId',
						   'SortDirection' => $date_sort );

		$params  =  array(
			'Token' => $this->token,
			'Filter' => $filter,
			'Sort' => $sort,
			);

		$method  =  "PROD_Get{$type}Products";
		$products  =  $this->client->$method( $params );

		$raw  =  $products->Products->any;

		$xml  =  new SimpleXMLElement( '<root>' . $raw . '</root>' );

		return $xml->Item->Product;
	}


	/**
	 * Initializes the SOAP Client using a specific WSDL for personify SSO 
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   void 
	 * @return  void
	**/

	function init_client( ) {

		if ( ! is_null( $this->client ) ) {
			return; // Already inited
		}

		$connection_params = $GLOBALS['config']['custom/personify']['connection_params'];

		$wsdl_url = $GLOBALS['config']['custom/personify']['personify_url'] . "/personifywebservice/universalwebservice/default.asmx?WSDL";


		$this->client  =  new SoapClient( $wsdl_url, $connection_params );

	}


}
