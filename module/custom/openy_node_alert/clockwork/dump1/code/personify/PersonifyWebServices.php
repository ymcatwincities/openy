<?php

load_section( 'custom/personify' );

abstract class PersonifyWebServices {


	public $client;

	public function __construct ( $client = null ) {

        $this->init_client( $client );
	}


	/**
	 * Initializes the SOAP Client using a specific WSDL
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * 
	 * @param   $client  SoapClient - allows forcing of $client for testing
	 * @return  void
	**/

	abstract function init_client( $client );

}
