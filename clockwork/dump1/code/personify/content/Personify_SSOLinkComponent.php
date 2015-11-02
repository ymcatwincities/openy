<?php

/**
 * Personify SSO Link Component
 *
 * @author <lloyd@clockwork.net>
 *
 * @package custom
 * @subpackage personify
**/

load_section( 'custom/personify' );

class Personify_SSOLinkComponent extends DynamicComponent {
	
	protected $linktext;	
	
	protected $destination_type;

	protected $redirect_page_id;	

	protected $external_url;

	protected $pws;
	
	function __construct ( ) {

		parent::__construct( );

		$this->component_type  =  'personify_sso_link';
		$this->_component_keys =  array( 'linktext', 'redirect_page_id', 'external_url', 'destination_type' );
		$this->_attributes     =  array( 'linktext', 'redirect_page_id', 'external_url', 'destination_type' );

		$this->_input_template_files  =  array( 'destination_external'  =>  'personify_sso_link_destination_external.html',
		                                        'destination_internal'  =>  'personify_sso_link_destination_internal.html' );
		$this->_allowed_actions  =  array( 'display', 'sso_login_invoke', 'sso_login_success' );
		$this->_default_action   =  'display';

		$this->soapclient = null;

		$this->destination_type  =  'external';

	}


	public function init_pws ( ) {

		try {
			$this->pws = new PersonifySSOWebServices( );
		}
		catch ( SoapFault $s ) { 

			$this->pws = null;

			$GLOBALS['logger']->log( "SoapFault thrown when initializing PersonifySSOWebServices faultcode: {$s->faultcode}, faultstring: {$s->faultstring}, faultactor: {$s->faultactor}, detail: {$s->detail}", CW_LOG_ERROR );

		}
		catch ( PersonifyDisabledException $e ) { 
			$this->pws = null;
		}

	}



	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$component_template  =  parent::populate_component_input_template( $component_template );

		$template_data = array( );

		$template_data['destination_section']  =  $this->_get_destination_type_template_file( );

		if ( $this->get_redirect_page_id( ) ) {
			$template_data['redirect_page_name']  =  get_page_name( $this->get_redirect_page_id( ) );
		}
		else {
			$template_data['redirect_page_name']  =  'No redirect page selected.';
		}

		$component_template->set_escaped( $template_data );

		// Populate your input template.

		return $component_template;
	}

	
	function populate_component_input_validator ( & $component_input_validator ) {
		
		$destination_type_xml_file  =  $this->get_validation_xml_directory() .
		                               '/personify_sso_link_destination_' . 
		                                $this->get_destination_type( ) . '.xml';
		
		$new_validator  =  new FormValidator( $destination_type_xml_file );
		$component_input_validator->merge_validator( $new_validator );
		
	}

	
	public function action_display ( ) {

		$template  =  $this->get_output_template( );

		$template->set( $this->to_hash() );

		$sso_login_invoke_url = $this->get_self_url( 'sso_login_invoke' );

		$template->set( array( 'sso_login_invoke_url' => $sso_login_invoke_url ) );

		return $template->render( );
	}


	public function action_sso_login_invoke( ) {

		$this->init_pws( );

		$template  =   $this->get_output_template( );

		$template->set( $this->to_hash() );

		$url = $this->get_self_url( 'sso_login_success' );

		if ( is_null( $this->pws ) ) {
			$template->set( array( 'personify_error' => true ) );
			return $template->render( );
		}

		try {
			$vendor_token = $this->pws->get_vendor_token( $url );
		}
		catch ( SoapFault $s ) { 

			$GLOBALS['logger']->log( "SoapFault thrown when fetching Personify vendor token.  faultcode: {$s->faultcode}, faultstring: {$s->faultstring}, faultactor: {$s->faultactor}, detail: {$s->detail}", CW_LOG_ERROR );

			$vendor_token  =  null;

			$template->set( array( 'personify_error' => true ) );

			return $template->render( );

		}

		$personify_sso_url = $GLOBALS['config']['custom/personify']['personify_url'] . '/' .
			$GLOBALS['config']['custom/personify']['sso'] .  '/login.aspx?vi=' .
		   	$GLOBALS['config']['custom/personify']['vendorID'] . '&vt=' . $vendor_token;

		redirect( $personify_sso_url );

	}

	public function action_sso_login_success( ) {

		$this->init_pws( );

		$form_data  =  $this->get_form_data( );
		$template   =  $this->get_output_template( );

		// Check for customer token.  If we don't have it, that's bad.  Log an alert.

		$encrypted_ct = $form_data['ct'];

		if ( $encrypted_ct == "" ) {
			$GLOBALS['logger']->log( "No customer token supplied in personify SSO callback.", CW_LOG_ALERT );
			$this->redirect_after_login( );
		}

		if ( is_null( $this->pws ) ) {
			$template->set( array( 'personify_error' => true ) );
			return $template->render( );
		}


		// $decrypt the CT

		try {
			$ct = $this->pws->decrypt_customer_token( $encrypted_ct );
		}
		catch ( SoapFault $s ) { 

			$GLOBALS['logger']->log( "SoapFault thrown when decrypting Personify vendor token.  faultcode: {$s->faultcode}, faultstring: {$s->faultstring}, faultactor: {$s->faultactor}, detail: {$s->detail}", CW_LOG_ERROR );

			$vendor_token  =  null;

			$template->set( array( 'personify_error' => true ) );

			return $template->render( );

		}

		if ( $ct != "" ) {
			$_SESSION['custom/personify']['customer_token'] = $ct;	
		}
		else {
			$GLOBALS['logger']->log( "Decrypted SSO customer token was empty", CW_LOG_ALERT );
			$this->redirect_after_login( );
		}

		// Use the CT to get customer data from personify.
		$personify_member_data = $this->pws->get_personify_member_data( $ct );

		// Sanity-check customer data

		if ( $personify_member_data['UserExists'] == false ) {
			$GLOBALS['logger']->log( "SSO Failed - UserExists returned false", CW_LOG_INFO );
			$this->redirect_after_login( );
			return;
		}


		// Are we logged in as an AMM member already?  If so, just update the session with the CT

		$current_member_email = Context::get_member_email( );

		if ( $current_member_email == $personify_member_data['Email'] ) {
			$this->redirect_after_login( );
			return;
		}


		// If not, let's get logged in.

		$member_email = $personify_member_data['Email'];

		$member_id = get_member_id_from_email( $member_email );
		
		if ( is_valid_id( $member_id ) ) {
			log_in_member_account( $member_id );
			$this->redirect_after_login( );
			return;
		}


		// If no existing member, create a new AMM member, and log them in.

		$new_member = array( 'member_email'   =>  $personify_member_data['Email'],
							 'member_status'  =>  'active',
							 'created_by'     =>  'Personify_SSOLinkComponent',
							 'created_on'     =>  get_current_date( 'mysql' ),
							 'created_by_id'  =>  $this->get_id( ) );

		$new_member_id = add_member( $new_member );


		// Let personify know what our ID for the member is

		$this->pws->set_customer_id_in_personify( $ct, $personify_member_data['UserName'], $new_member_id );
		log_in_member_account( $new_member_id );
		$this->redirect_after_login( );

	}


	function redirect_after_login( ) {

		$message = new InfoMessage( M('You are signed in!') );

		if ( is_valid_id( $this->redirect_page_id ) ) {
			redirect( get_page_context_url( $this->redirect_page_id ), $message );
		}
		else if ( ! empty( $this->external_url ) ) {
			redirect( $this->external_url );
		}
		else {
			$this->redirect_to_action( 'display', $message );
		}

	}

		
	function _get_destination_type_template_file ( ) {

		$template_file_name  =  'destination_'.$this->get_destination_type( ); 
		$destination_type_template_file  =  $this->get_named_input_template_file( $template_file_name );

		return $destination_type_template_file;
	}


	public function get_linktext ( ) {
		return $this->linktext;
	}

	
	public function set_linktext ( $value ) {
		$this->linktext  =  $value;
	}
		
	public function get_redirect_page_id ( ) {
		return $this->redirect_page_id;
	}
	
	public function set_redirect_page_id ( $value ) {
		$this->redirect_page_id  =  $value;
	}
	
	public function get_external_url ( ) {
		return $this->external_url;
	}
	
	public function set_external_url ( $value ) {
		$this->external_url  =  $value;
	}

	function get_destination_type ( ) {
		return $this->destination_type;
	}

	function set_destination_type ( $destination_type ) {
		$this->destination_type  =  $destination_type;
	}

}
