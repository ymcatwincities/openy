<?php

/**
 * Personify Class Description Component
 *
 * @author <selah@clockwork.net>
 *
 * @package custom
 * @subpackage personify
**/

class Personify_ClassDescriptionComponent extends DynamicComponent {
	
	protected $product_code;	
	
	function __construct ( ) {

		parent::__construct( );

		$this->component_type  =  'personify_class_description';
		$this->_component_keys  =  $this->_attributes  =  array( 'product_code' );

		$this->_allowed_actions  =  array( 'display' );
		$this->_allowed_post_actions  =  array( );
		$this->_default_action   =  'display';

	}


	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$component_template  =  parent::populate_component_input_template( $component_template );
		
		// Populate your input template.

		return $component_template;
	}
	
	
	public function action_display ( ) {

		$template  =  $this->get_output_template( );

		$template->set( $this->to_hash() );
		if ( ! $this->get_product_code( ) ) {
			return $this->fail( );
		}

		$ws  =  new PersonifyUniversalWebServices( );
		$ws->authenticate( );

		$products  =  $ws->search_by_product_code( $this->get_product_code( ), $date_sort = 'Descending' );

		if ( ! $products ) {
			return $this->fail( );
		}
		else {
            // We always show the first result in the date-sorted list
			$template->set( array(
								'description' => $products[0]->LongName,
								'name'        => $products[0]->ShortName,
								'code'        => $products[0]->ProductCode,
								) );
		}

		return $template->render( );
	}


	private function fail ( ) {
		return '';
	}


		
	public function get_product_code ( ) {
		return $this->product_code;
	}

	
	public function set_product_code ( $value ) {
		$this->product_code  =  $value;
	}
	
}
