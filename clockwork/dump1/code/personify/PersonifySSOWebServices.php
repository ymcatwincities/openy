<?php

load_section( "custom/personify" );

class PersonifySSOWebServices extends PersonifyWebServices {

	var $base_params;

	public function __construct ( $client = null ) {

		parent::__construct( $client );

		if ( FeatureSwitch::is_enabled( 'personify_sso_kill_switch' ) ) {
			$GLOBALS['logger']->log( "personify_sso_kill_switch is enabled.  Skipping Personify SSO init.", CW_LOG_DEBUG );
			throw new PersonifyDisabledException( 'Personify SSO is disabled' );
		}

		$this->base_params  =  array(
			'vendorUsername' => $GLOBALS['config']['custom/personify']['vendorUsername'],
			'vendorPassword' => $GLOBALS['config']['custom/personify']['vendorPassword'],
			'vendorBlock'    => $GLOBALS['config']['custom/personify']['vendorBlock']
			);

	}


	/**
	 * Given a customer token, fetches a personify's UserName and Email
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   mixed  $customer_token
	 * @return  array  $member_data
	**/

	function get_personify_member_data( $customer_token ) {

		if ( is_null( $this->client ) ) {
			return null;
		}

		$params  =  $this->base_params;
		$params['customerToken'] = $customer_token;

		$response  =  $this->client->SSOCustomerGetByCustomerToken( $params );

		$member_data = array( 'UserExists' => $response->SSOCustomerGetByCustomerTokenResult->UserExists,
							  'UserName' => $response->SSOCustomerGetByCustomerTokenResult->UserName,
							  'Email' => $response->SSOCustomerGetByCustomerTokenResult->Email
							);

		return $member_data;

	}


	/**
	 * Logs the member out of personify.
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   mixed  $customer_token
	 * @return  object $response
	**/

	function logout( $customer_token ) {

		if ( is_null( $this->client ) ) {
			return null;
		}

		$params  =  $this->base_params;
		$params['customerToken'] = $customer_token;

		$response  =  $this->client->SSOCustomerLogout( $params );

		return $response;

	}


	/**
	 * Gets a vendor token from a personify installation. 
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   mixed  $url  The url to redirect to following a successful login.
	 * @return  mixed  $encrypted_token
	**/

	function get_vendor_token( $url ) {

		if ( is_null( $this->client ) ) {
			return null;
		}

		$params  =  $this->base_params;
		$params['url'] = $url;

		$response  =  $this->client->VendorTokenEncrypt( $params );
		$encrypted_token = $response->VendorTokenEncryptResult->VendorToken;

		return $encrypted_token;

	}


	/**
	 * Validates / updates session customer token
	 * The act of validation invalidates the customer token.  Therefore,
	 * A new valid token will be returned upon validation success.
	 * The session is updated with the new token.
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   void
	 * @return  object $response
	**/

	function validate_session_customer_token(  ) {

		if ( is_null( $this->client ) || ! array_get( $_SESSION, 'custom/personify' ) ) {
			return null;
		}

		$customer_token  =  array_get( $_SESSION['custom/personify'], 'customer_token', $default = null );

		if ( is_null( $customer_token ) ) {
			return false;
		}

		$params  =  $this->base_params;
		$params['customerToken']  =  $customer_token;

		$response  =  $this->client->SSOCustomerTokenIsValid( $params );
		$new_token = $response->SSOCustomerTokenIsValidResult->NewCustomerToken;

		if ( is_null( $new_token ) ) {
			$GLOBALS['logger']->log( "Null token returned from SSOCustomerTokenIsValidResult.  Setting session customer token to null.", CW_LOG_DEBUG );
		}

		$_SESSION['custom/personify']['customer_token'] = $new_token;


		return $response;
	}


	/**
	 * Following a successful SSO login (personify login + AMM login), 
	 * informs personify of the unique id (member id) of the logged-in AMM member.
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   mixed  	$customer_token
	 * @param   mixed  	$personify_username
	 * @param   id  	$member_id   AMM member id
	 * @return  object  $response
	**/

	function set_customer_id_in_personify( $customer_token, $personify_username, $member_id ) {

		if ( is_null( $this->client ) ) {
			return null;
		}

		$params  =  $this->base_params;
		$params['customerToken'] = $customer_token;
		$params['UserName'] = $personify_username;
		$params['TIMSSCustomerIdentifier'] = $member_id;

		$response  =  $this->client->TIMSSCustomerIdentifierSet( $params );

		return $response;
	}

	/**
	 * Uses personify's decrypt web service to decrypt a token  
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   mixed  $encrypted_customer_token
	 * @return  void
	**/

	function decrypt_customer_token( $encrypted_customer_token ) {

		if ( is_null( $this->client ) ) {
			return null;
		}

		$params  =  $this->base_params;
		$params['customerToken'] = $encrypted_customer_token;

		$response  =  $this->client->CustomerTokenDecrypt( $params );
		$decrypted_token = $response->CustomerTokenDecryptResult->CustomerToken;

		return $decrypted_token;

	}


	/**
	 * Initializes the SOAP Client using a specific WSDL for personify SSO 
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   $client  SoapClient - allows forcing of $client for testing
	 * @return  void
	**/

	function init_client( $client = null ) {

		if ( ! is_null( $client ) ) {
			$this->client = $client;
		}

		if ( ! is_null( $this->client ) ) {
			return; // Already inited
		}

		$wsdl_url = $GLOBALS['config']['custom/personify']['wsdl_url'];

		$connection_params = $GLOBALS['config']['custom/personify']['connection_params'];

		try { 
			$this->client  =  new CwSoapClient( $wsdl_url, $connection_params );
		}
		catch ( Exception $e ) {
			// Trap exception here.
			$this->client  =  null;
			throw $e;
		}
	}
}


class PersonifyDisabledException extends Exception {}
