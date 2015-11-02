<?php

/**
 * YMCA Twin Cities GroupEx Search Form
 *
 * @author <lloyd@clockwork.net>
 * @author <nate@clockwork.net>
 *
 * @package custom
 * @subpackage groupex
**/

load_section( 'custom/groupex' );

class YMCATwinCities_GroupExSearchFormComponent extends DynamicComponent {
	
	protected $location_mode;	
	
	protected $location_value;	
	
	protected $category_mode;	
	
	protected $category_value;	
	
	protected $class_name_mode;	
	
	protected $class_name_value;	

	protected $show_date_picker;

	protected $search_results_page_id;
	
	public function __construct ( ) {

		parent::__construct( );

		$this->component_type      =  'ymca_twin_cities_group_ex_search_form';
		$this->_component_keys = array( 'location_mode', 'location_value', 'category_mode', 'category_value', 'class_name_mode', 'class_name_value', 'show_date_picker', 'search_results_page_id' );
		$this->_attributes	 = $this->_component_keys;

		$this->_allowed_actions  =  array( 'display' );
		$this->_default_action   =  'display';

	}

	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$gws = new GroupEx_WebServices( );

		$component_template  =  parent::populate_component_input_template( $component_template );
		
		$search_results_page_id  =  $this->get_search_results_page_id( );

		if ( is_valid_id( $search_results_page_id ) ) {
			$search_results_page_name  =  get_page_name( $search_results_page_id );
			$component_template->set_escaped( array( 'search_results_page_name' =>  $search_results_page_name ) );
		}

		try {

			if ( $this->get_location_mode( ) == 'fixed' ) {
				$component_template->set_loop_variables( 'locations', $gws->get_locations( ) );
			}

			if ( $this->get_category_mode( ) == 'fixed' ) {
				$component_template->set_loop_variables( 'categories', $gws->get_categories( ) );
			}

			if ( $this->get_class_name_mode( ) == 'fixed' ) {
				$component_template->set_loop_variables( 'class_names', $gws->get_classes( ) );
			}

		}
		catch ( Exception $e ) {

			// Log an alert if it's any other type of exception so that an
			// email is generated.
			if ( ! $e instanceof GroupEx_Exception ) {
				$GLOBALS['logger']->log( "Exception: {$e}", CW_LOG_ALERT );
			}

			// Catch all types of exceptions and present the user with a nice 
			// message instead.
			$component_template->set( array( 'groupex_error' => true ) );
			return $component_template;
		}

		$component_template->set( array( 'location_mode_' . $this->get_location_mode( ) => true,
										 'category_mode_' . $this->get_category_mode( ) => true,
										 'class_name_mode_' . $this->get_class_name_mode( ) => true ) );

		return $component_template;
	}

	
	
	/**
	 * Renders the output template with the appropriate variables.
	 *
	 * @author Lloyd <lloyd@clockwork.net>
	 * @author dave <dave@clockwork.net>
	 *
	**/
	public function action_display ( ) {

		$template  =  $this->get_output_template( );
		
		$template->set( $this->to_hash() );

		//Set preview only form params to make the form work in preview mode
		if ( is_valid_id( $this->search_results_page_id ) ) {
			$preview_hidden_form_inputs  =  $this->get_hidden_preview_params_for_page_id( $this->get_search_results_page_id( ) );
		}
		else {
			$preview_hidden_form_inputs  =  $this->get_hidden_preview_params_for_page_id( $this->get_site_page_id( ) );
		}

		$template->set( array( 'preview_hidden_form_inputs' => $preview_hidden_form_inputs ) );
		

		$gws = new GroupEx_WebServices( );

		try {

			if ( $this->get_location_mode( ) == 'user_selectable' ) {
				$template->set_loop_variables( 'locations', $gws->get_locations( ) );
			}

			if ( $this->get_category_mode( ) == 'user_selectable' ) {
				$template->set_loop_variables( 'categories', $gws->get_categories( ) );
			}

			if ( $this->get_class_name_mode( ) == 'user_selectable' ) {
				$template->set_loop_variables( 'classes', $gws->get_classes( ) );
			}

		}
		catch ( Exception $e ) {

			// Log and e-mail the exception info
			if ( ! $e instanceof GroupEx_Exception ) {
				$GLOBALS['logger']->log( "Exception: {$e}", CW_LOG_ALERT );
			}

			// Catch all types of exceptions and present the user with a nice 
			// message instead.
			$template->set( array( 'groupex_error' => true ) );
			return $template;
		}

		if ( is_valid_id( $this->search_results_page_id ) ) {
			$target_url = get_page_context_url( $this->search_results_page_id, Context::is_ssl( ) );
		}
		else {
			$target_url = "";
		}

		$template->set( array( 'target_url' => $target_url,
								'location_mode_' . $this->get_location_mode( ) => true,
								'category_mode_' . $this->get_category_mode( ) => true,
								'class_name_mode_' . $this->get_class_name_mode( ) => true ) );

		$template->set_loop_variables( 'filter_times',   $this->get_filter_length( ) );

		$template->set_soft_rendering( );
		$template->set_backslash_escaping( );

		return $template->render( );
	}


		
	public function get_location_mode ( ) {
		return $this->location_mode;
	}

	
	public function set_location_mode ( $value ) {
		$this->location_mode  =  $value;
	}
		
	public function get_location_value ( ) {
		return $this->location_value;
	}

	
	public function set_location_value ( $value ) {
		$this->location_value  =  $value;
	}
		
	public function get_category_mode ( ) {
		return $this->category_mode;
	}

	
	public function set_category_mode ( $value ) {
		$this->category_mode  =  $value;
	}
		
	public function get_category_value ( ) {
		return $this->category_value;
	}

	
	public function set_category_value ( $value ) {
		$this->category_value  =  $value;
	}
		
	public function get_class_name_mode ( ) {
		return $this->class_name_mode;
	}

	
	public function set_class_name_mode ( $value ) {
		$this->class_name_mode  =  $value;
	}
		
	public function get_class_name_value ( ) {
		return $this->class_name_value;
	}

	
	public function set_class_name_value ( $value ) {
		$this->class_name_value  =  $value;
	}
			
	public function get_show_date_picker ( ) {
		return $this->show_date_picker;
	}

	
	public function set_show_date_picker ( $value ) {
		$this->show_date_picker  =  $value;
	}
		
	public function get_search_results_page_id ( ) {
		return $this->search_results_page_id;
	}

	
	public function set_search_results_page_id ( $value ) {
		$this->search_results_page_id  =  $value;
	}

	public function get_filter_length( ) {

		return array( array( 'length' => 'day',   'label' => 'Day' ),
					  array( 'length' => 'week',  'label' => 'Week' ),
				);
	}

	
}


