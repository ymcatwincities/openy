<?php

/**
 * Personify Advanced Product Search Link Component
 *
 * @author <das@clockwork.net>
 * @author Nate Volker <nate@clockwork.net>
 *
 * @package custom
 * @subpackage personify
**/

load_section( "custom/personify" );

class Personify_AdvancedProductSearchLinkComponent extends DynamicComponent {

	protected $link_text;
	
	protected $branch_code;	
	
	protected $category_code;
	
	protected $subcategory_code;
	
	protected $product_long_name;
	
	protected $display_mode;
	
	protected $branches_filter;

	protected $autosearch;
	
	function __construct ( ) {

		parent::__construct( );

		$this->component_type  =  'personify_advanced_product_search_link';
		$this->_component_keys = array(
			'link_text',
			'branch_code',
			'category_code',
			'subcategory_code',
			'product_long_name',
			'display_mode',
			'branches_filter',
			'autosearch',
		);
		$this->_attributes	 = array(
			'link_text',
			'branch_code',
			'category_code',
			'subcategory_code',
			'product_long_name',
			'display_mode',
			'branches_filter',
			'autosearch',
		);

		$this->set_display_mode('static_link');

		$this->_allowed_actions  =  array( 'display' );
		$this->_default_action   =  'display';

	}


	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$component_template  =  parent::populate_component_input_template( $component_template );
		
		// Populate your input template.
		$component_template->set( array( 
			'advanced_search_url' => $this->build_advanced_search_url( ),
			'display_mode_' . $this->get_display_mode( ) => true 
		) );
										 		
		$branches  =  $this->get_branches( array( ), true );
		$component_template->set_loop_variables( 'branch_groups', $branches );

		return $component_template;
		
	}


	public function action_display ( ) {

		$template   =  $this->get_output_template( );
		$form_data  =  $this->get_form_data( );
		
		$template->set( $this->to_hash() );
		
		$template_params  =  array(
			'advanced_search_url'                         =>  $this->build_advanced_search_url( ),
			'advanced_search_target_url'                  =>  $GLOBALS['config']['custom/personify']['advanced_search_url'],
			'display_mode_' . $this->get_display_mode( )  =>  true
		);
					
		if ( $this->get_display_mode( ) === 'location_dropdown' ) {
		
			$allowed_branches                  =  $this->get_branches_filter( );
			$branches                          =  $this->get_branches( $allowed_branches, true );
			$template_params['has_optgroups']  =  ( count( $branches ) > 1 );
			$template->set_loop_variables( 'branch_groups', $branches );
			
		}
		
		$template->set( $template_params );

		return $template;
		
	}
	
	/**
	 * build_advanced_search_url( )
	 * 
	 * builds a url based on config.local.php and attributes set in amm 
	 *
	 * @param none
	 * @return string $advanced_search_url
	**/
	public function build_advanced_search_url ( ) {
		
		$advanced_search_url  =  $GLOBALS['config']['custom/personify']['advanced_search_url'];
		$advanced_search_url  .=  '?';

		//get our possible params
		$branch  =  $this->get_branch_code( );
		$cat_code  =  $this->get_category_code( );
		$sub_cat_code  =  $this->get_subcategory_code( );
		$long_name  =  $this->get_product_long_name( );
		
		//stockpile our params
		$param_pile  =  array( );

		if ( $branch ) {
			$param_pile['brcode']  =  $branch;
		}

		if ( $cat_code ) {
			$param_pile['catcode']  =  $cat_code;
		}
		
		if ( $sub_cat_code ) {
			$param_pile['SubCatCode']  =  $sub_cat_code;
		}
		
		if ( $long_name ) {
			$param_pile['longname']  =  $long_name;
		}

		if ( $this->get_autosearch( ) ) {
			$param_pile['autosearch']  =  'Y';
		}
		
		$advanced_search_url  .=  urlify_array( $param_pile );
		
		return $advanced_search_url;
	}

	/**
	 * get_branches( )
	 * 
	 * get the branches and their personify ids from the config
	 * if $allowed_branches is set only return branches whose brcode's match
	 *
	 * @param array $allowed_branches array of brcodes that to display
	 * @return array $branches
	**/	
	public function get_branches ( $allowed_branches = array( ), $group_by_tag = false ) {
	
		$locations_array  =  $GLOBALS['config']['custom/personify']['locations'];
		$branches         =  array( );
				
		if ( ! is_array( $allowed_branches ) ) {
			$allowed_branches  =  array( $allowed_branches );
		}
				
		foreach( $locations_array as $location ) {
		
			$brcode       =  $location['personify_brcode'];
			$branch_name  =  $location['name'];
			
			if ( isset( $brcode ) && ! ( $brcode === '') ) {			

				if  ( count( $allowed_branches ) == 0 || in_array( $brcode, $allowed_branches ) ) {

					$new_location = $location;
					$location_type = strtolower( $location['tags'][0] );

					$new_location['tag']                             =  $location_type;
					$new_location['location_type_'. $location_type]  =  $location_type;
					$new_location['branch_name']                     =  $branch_name;
					$new_location['brcode']                          =  $brcode;
					$branches[]                                      =  $new_location;
				}
				
				$names  =  array( );
				foreach ($branches as $key => $location ) {
			    	$names[$key]  =  $location['name'];
				}

				$names  =  array_map('strtolower', $names);
				array_multisort( $names, SORT_STRING, $branches );

			}
		
		}
		
		if ( $group_by_tag ) {
		
			$location_types    =  $GLOBALS['config']['custom/personify']['location_types'];
			$grouped_branches  =  array( );
			
			foreach ( $location_types as $location_type ) {
				$location_type['branches']                =  array( );
				$location_type['tag']                     =  strtolower( $location_type['tag'] );
				$grouped_branches[$location_type['tag']]  =  $location_type;
			} 
			
			foreach ( $branches as $branch ) {
				if ( isset( $grouped_branches[$branch['tag']] ) ) {
					$grouped_branches[$branch['tag']]['branches'][]  =  $branch;
				}
			}
			
			foreach( $grouped_branches as $key => $group ) {
				if ( count( $group['branches'] ) === 0 ) {
					unset( $grouped_branches[$key] );
				}
			}
			
			$branches  =  array_values( $grouped_branches );
			
		}
		
		return $branches;
		
	}

	public function get_link_text ( ) {
		return $this->link_text;
	}
	
	public function set_link_text ( $value ) {
		$this->link_text  =  $value;
	}

		
	public function get_branch_code ( ) {
		return $this->branch_code;
	}

	
	public function set_branch_code ( $value ) {
		$this->branch_code  =  $value;
	}
		
	public function get_category_code ( ) {
		return $this->category_code;
	}

	
	public function set_category_code ( $value ) {
		$this->category_code  =  $value;
	}
		
	public function get_subcategory_code ( ) {
		return $this->subcategory_code;
	}

	
	public function set_subcategory_code ( $value ) {
		$this->subcategory_code  =  $value;
	}
		
	public function get_product_long_name ( ) {
		return $this->product_long_name;
	}

	
	public function set_product_long_name ( $value ) {
		$this->product_long_name  =  $value;
	}
	
	public function get_display_mode ( ) {
		return $this->display_mode;
	}
	
	public function set_display_mode ( $value ) {
		$this->display_mode  =  $value;
	}

	public function get_branches_filter ( ) {
		return $this->branches_filter;
	}
	
	public function set_branches_filter ( $value ) {
		$this->branches_filter  =  $value;
	}


	public function get_autosearch ( ) {
		return $this->autosearch;
	}


	public function set_autosearch ( $autosearch ) {
		$this->autosearch  =  (boolean) $autosearch;
		return true;
	}

}

