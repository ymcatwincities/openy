<?php

load_section( 'custom/personify' );

class PersonifySSOWebServicesTestCase extends \Cw\Shared\Testing\SimpleTestCompatibleUnitTestCase {

	private $wsdl;

	function setUp ( ) {
		// Now hitting a remote host on pion instead our own AMM. Bug #4869.
		$this->wsdl  =  'service-test.dev.clockwork.net/tests/soap/slow-wsdl.php';
		FeatureSwitch::disable( 'personify_sso_kill_switch' );
	}

	function test_success_with_no_timeout ( ) {

		$client  =  $this->client( );

		$ws = new PersonifySSOWebServices( $client );

		$this->pass();

	}


	function test_killswitch ( ) {

		FeatureSwitch::enable( 'personify_sso_kill_switch' );

		$client  =  $this->client( );

		try {
			$ws = new PersonifySSOWebServices( $client );
		}
		catch ( PersonifyDisabledException $e ) {
			$this->pass();
			return;
		}

		$this->fail( "Failed to throw PersonifyDisabledException when personify_sso_kill_switch enabled  " );

	}


	private function wsdl ( $scheme, $params ) {
		$url  =  URLs::append_parameters( $this->wsdl, $params );
		return "$scheme://$url";
	}


	private function client ( $args = array( ) ) {

		$options['cache_wsdl']         =  WSDL_CACHE_NONE;
		$options['connection_timeout'] =  array_get( $args, 'timeout', 10 );

		$params['wsdl_sleep']    =  array_get( $args, 'wsdl_sleep', 0 );
		$params['service_sleep'] =  array_get( $args, 'service_sleep', 0 );

		$wsdl = array_get( $args, 'wsdl', $this->wsdl );

		$scheme  =  array_get( $args, 'scheme', 'http' );
		$url  =  URLs::append_parameters( $wsdl, $params );
		$url  =  "$scheme://$url";

		$client  =  new CwSoapClient( $url, $options );

		return $client;
	}
}
