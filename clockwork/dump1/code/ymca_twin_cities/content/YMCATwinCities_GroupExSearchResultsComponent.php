<?php

/**
 * YMCA Twin Cities GroupEx Search Component
 *
 * @author <lloyd@clockwork.net>
 * @author <nate@clockwork.net>
 *
 * @package custom
 * @subpackage groupex
**/

load_section( 'custom/groupex' );

class YMCATwinCities_GroupExSearchResultsComponent extends DynamicComponent {
	
	protected $_output_validation_file;
	protected $_validator;
	protected $output_mode;
	
	public function __construct ( ) {

		parent::__construct( );

		$this->component_type          =  'ymca_twin_cities_group_ex_search_results';
		$this->_component_keys         =  array( 'output_mode' );
		$this->_attributes	           =  array( 'output_mode'  );
		$this->_allowed_actions        =  array( 'display', 'search' );
		$this->_default_action         =  'display';
		$this->_output_template_files  =  array(
			'results_default'      =>  'ymca_twin_cities_group_ex_search_results_default.html',
			'results_by_location'  =>  'ymca_twin_cities_group_ex_search_results_by_location.html',
		);
		
		$this->set_output_validation_xml( 
			$GLOBALS['xml_directory'] . '/custom/ymca_twin_cities/content/forms/' .
			'component_outputs/ymca_twin_cities_group_ex_search_results.xml' 
		);
		
		$this->_validator  =  new FormValidator( $this->get_output_validation_xml( ) );

	}


	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$component_template  =  parent::populate_component_input_template( $component_template );
		
		// Populate your input template.

		return $component_template;
		
	}

	
	/**
	 * The search action is the same as the display action because the components use GETS and url params to determine state.
	 * Action search is only used if converting the component to a post to save form params in the session before redisplay.
	 *
	 * @author dave <dave@clockwork.net>
	 *
	**/
	public function action_search ( ) {
		return $this->action_display( );
	}


	/**
	 * Renders the output template with the appropriate variables.
	 *
	 * @author dave <dave@clockwork.net>
	 * @author nate <nate@clockwork.net>
	**/
	public function action_display ( ) {

		$gws                     =  new GroupEx_WebServices( );
		$template                =  $this->get_output_template( );
		$form_data               =  $this->get_form_data( );
		$requested_location_ids  =  force_to_array( 
			array_get( $form_data, 'location', array( ) ) 
		);
		
		$template->set( $this->to_hash( ) );

		//Set preview only form params to make the form work in preview mode
		$preview_hidden_form_inputs  =  $this->get_hidden_preview_params_for_page_id( 
			$this->get_site_page_id( ) 
		);
		
		$template->set( array( 
			'preview_hidden_form_inputs' => $preview_hidden_form_inputs 
		) );
		
		$template->set( $this->_load_settings( ) );
		$template->set_loop_variables( 'filter_times', $this->get_filter_length( ) );
		
		try {
		
			$template->set_loop_variables( 'classes',    $gws->get_classes( ) );
			$template->set_loop_variables( 'categories', $gws->get_categories( ) );

			// Save the locations because we need to refer to them later
			$locations  =  $gws->get_locations( );
			$template->set_loop_variables( 'locations',  $locations );
			
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

		// Search section of template populated, time to look at results.
		if( ! $this->_validator->is_valid( ) ) {
		
			$temp  =  $this->_validator->get_error_message( );
			$temp  =  $this->_validator->get_error_detail_hashes( );

			$this->_validator->store_error_message_in_session( );
			$this->_validator->store_error_detail_hashes_in_session( );
			$template->set( array( 'groupex_searched' => false ) );
			
			return $template;
			
		}

		$req_data  =  $this->_get_request_params( );

		if ( is_array( $req_data ) === false ) {
			$template->set( array( 'groupex_searched' => false ) );
			return $template;
		}
		
		try {
			// This is where most of the timeouts happen
			$schedule  =  $gws->get_schedule_by_location( $req_data );
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

		// Look-Up-Tables for translating a location ID to a location name
		// and vice versa
		$location_lut                          =  array( );
		$location_name_lut                     =  array( );
		$location_print_urls_by_location_name  =  array( );

		foreach( $locations as $location ) {
			$location_lut[$location['id']]         =  $location['name'];
			$location_name_lut[$location['name']]  =  $location['id'];
		}

		if ( count( $requested_location_ids ) > 0 ) {
		
			foreach( $schedule as & $day ) {
	
				if ( is_array( $day ) ) {
				
					foreach( $day as & $day_info ) {

						foreach( $day_info['locations'] as & $location ) {

							// Fetch the print URLs for the location and day
							$location_print_urls  =  $this->get_print_urls( 
								$location_name_lut[$location['location_name']], 
								array_get( $req_data, 'category', 'category' ),
								$day_info['day']
							);

							$location =  array_merge( 
								$location, 
								$location_print_urls
							);
							
						}
						
					}
		
				}
							
			}	
			
		}
		
		// Don't use 'results_by_location' template if we didn't request any specific location
		if ( count( $requested_location_ids ) > 0 ) {
			$results_template_file  =  $this->get_named_output_template_file( 
				'results_by_location' 
			);
		}
		
		else {
			$results_template_file  =  $this->get_named_output_template_file( 
				'results_default' 
			);
		}

		$results_template = new DynamicTemplate( $results_template_file );
		
		$results_template->set_loop_variables( 
			'schedule_results',  
			$schedule['schedule'] 
		);
		
		$results_template->set( array( 
			'n_results' =>  $schedule['n_classes'], 
			'groupex_searched' => true 
		) );

		$template->set( array( 
			'n_results' =>  $schedule['n_classes'], 
			'groupex_searched' => true,
			'results_html' => $results_template->render( ),
		) );
		
		$template->set_soft_rendering( );
		$template->set_backslash_escaping( );
		
		return $template;
		
	}


	/**
	 * Construct URLs pointing to GroupExPro's PDF export utility with the
	 * specified search criteria for use in the component's output. These
	 * were reverse engineered and are not truly part of their API.
	 *
	 * @param   array  $req_data  Array of filter criteria
	 * @return  array  Hash to be used as template output variables
	**/
	protected function get_print_urls ( $location_id="", $category_id="", $start ) {

		$params['l']     =  $location_id;
		$params['c']     =  $category_id;

		// GroupExPro is not using UTC and their PDF generation breaks if
		// it doesn't receive a timestamp for the Monday of the week specified
		// at midnight in Mountain Standard Time. @todo: tell them to use UTC?
		// GroupExPro's week starts on Monday! If it's Monday use "this",
		// otherwise use "last"
		$timestamp       =  strtotime( $start );
		$day_of_week     =  date( "w", $timestamp );
		$week_modifier   =  ( $day_of_week == 1 ? 'this' : 'last' );

		// Generate the preceeding Monday of the date being requested
		$request_date    =  strtotime( "{$week_modifier} Monday MST ".date( "m/d/Y", $timestamp ) );
		$params['week']  =  $request_date;

		$account_id   =  $GLOBALS['config']['custom/groupex']['groupex_account_id'];

		$print_url    =  URLs::append_parameters( $GLOBALS['config']['custom/groupex']['groupex_print_url'],
		                          array_merge( array( 'account' => $account_id ), $params ) );

		$larger_url   =  URLs::append_parameters( $GLOBALS['config']['custom/groupex']['groupex_print_url'], 
		                          array_merge( array( 'font' => 'larger',
		                                              'account' => $account_id ), $params ) );

		$desc_url     =  URLs::append_parameters( $GLOBALS['config']['custom/groupex']['groupex_desc_url'], 
		                          array_merge( array( 'account' => $account_id ), $params ) );

		$print_urls   =  array( 'print_url'                =>  $print_url, 
			                    'print_larger_url'         =>  $larger_url,
			                    'print_description_url'    =>  $desc_url );

		return $print_urls;
	}


	/**
	 * Loads the request params from the form data.  Retuns an array of the request params or 
	 * FALSE if the a search cannot be run.
	 *
	 * @author dave <dave@clockwork.net>
	 *
	 * @return array|FALSE    Returns an array of formatted search params, FALSE if a search cannot be run. 
	**/
	protected function _get_request_params ( ) {

		$class_search     =  $this->get_field_value( 'class' )    !== 'any' ? $this->get_field_value( 'class' )    : '';
		$category_search  =  $this->get_field_value( 'category' ) !== 'any' ? $this->get_field_value( 'category' ) : '';
		$location_search  =  $this->get_field_value( 'location' ) !== 'any' ? $this->get_field_value( 'location' ) : '';

		if( empty( $class_search ) && empty( $category_search ) && empty( $location_search ) ) {
			return false; //Unable to run a search with no filters, too many results and it takes too long
		}

		$request_params  =  array( );
		if( ! empty( $class_search )  ) { 
			$request_params['class']  =  "DESC--[{$class_search}";
		}

		if( ! empty( $category_search )  ) { 
			$request_params['category']  =  $category_search;
		}

		if( ! empty( $location_search )  ) { 
			$request_params['location']  =  $location_search;
		}

		$filter_date  =  $this->get_field_value( 'filter_date' );

		if ( ! $filter_date ) {
			// if no date is specified, ensure the date starts on a day boundary.
			$dt  =  new DateTime( DateHelper::format_human_date( time( ) ) );
		}
		else {
			$filter_date  =  strtolower( str_replace( '-', '/', $filter_date ) );
			$dt           =  new DateTime( $filter_date );
		}

		$request_params['start']  =  $dt->format( DateHelper::FORMAT_UNIX_TIMESTAMP );
		$debug_filter  =  $dt->format( DateHelper::FORMAT_MYSQL_DATE );
		
		$filter_length  =  $this->get_field_value( 'filter_length' );

		//start and end on the same day will return one day of results, time is updated to start of day and end of day by the API
		//documentation: http://www.groupexpro.com/ymcatwincities/json.php 
		switch( $filter_length ) {
			case 'month':
				$dt->modify( '+1 month' );
				$dt->modify( '-1 day' );
				break;
			case 'week':
				$dt->modify( '+1 week' );
				$dt->modify( '-1 day' );
				break;
			case 'day':
			default:
				break;
		}

		$request_params['end']  =  $dt->format( DateHelper::FORMAT_UNIX_TIMESTAMP );
		$debug_filter  =  $dt->format( DateHelper::FORMAT_MYSQL_DATE );

		$time_of_day = $this->get_field_value( 'time_of_day' );

		$time_filter = ( is_array( $time_of_day) && count( $time_of_day) > 0 );

		if ( $time_filter ) {

			$request_params['time_filter']  =  $time_filter;
			$request_params['morning']      =  in_array( 'morning', $time_of_day );
			$request_params['afternoon']    =  in_array( 'afternoon', $time_of_day );
			$request_params['evening']      =  in_array( 'evening', $time_of_day );

		}
		
		return $request_params;
	}

	
	/**
	 * Loads setting for the component.  If the form variables are available sets those.  Otherwise configures defaults.
	 *
	 * @author Dave Dohmier <dave@clockwork.net>
	 * 
	 * @return  array  Returns the settings configured for running the search ( defaults or submitted form ).
	**/
	protected function _load_settings ( ) {

		$search_settings  =  array( );
		// If multiple locations are clicked, only 1 day's worth of results are allowed (for performance)

		if ( count( array_get( $this->_form_data, 'location' ) ) > 1 
		  && array_get( $this->_form_data, 'filter_length' ) != 'day' ) {
			$this->_form_data['filter_length'] = 'day';
			$_SESSION[ 'template_variables'][ 'message' ] = M("Search results across multiple locations are limited to a single day.");
		}

		//form data contains search settings 
		$search_settings  =  $this->get_form_data( );

		$this->_init_default( $search_settings['class'],         'any' );
		$this->_init_default( $search_settings['category'],      'any' );
		$this->_init_default( $search_settings['location'],      'any' );
		$this->_init_default( $search_settings['filter_length'], 'day' );
		$this->_init_default( $search_settings['filter_date'],   DateHelper::format_human_date( time( ) ) );

		$this->_validator->validate( $search_settings ); //validate data
		//save any selected, checked, and error styles in the form
		$this->_validator->store_form_field_data_in_hash( $search_settings, $search_settings );

		return $search_settings;
	}


	protected function _init_default( & $item, $default ) {

		if( isset( $item ) === false ) {
			$item = $default;
		}
		return;
	}


	/**
	 * A simple check to determine if we are actually running a search or are just displaying the default component.
	 *
	 * @author Dave Dohmier <dave@clockwork.net>
	 * 
	 * @return  bool  TRUE if running a search, FALSE if not.
	**/	
	protected function is_search ( ) {

		$search  =  $this->get_field_value( 'groupex_search' );
		if( isset( $search ) ) {
			return true;
		}
		return false;
	}


	public function get_filter_length( ) {

		return array( array( 'length' => 'day',   'label' => 'Day' ),
					  array( 'length' => 'week',  'label' => 'Week' ),
				);
	}


	public function set_output_validation_xml ( $file ) {
	
		$this->_output_validation_file  =  $file;
	}


	public function get_output_validation_xml ( ) {
		
		return $this->_output_validation_file;
	}


	public function get_output_mode ( ) {
		return $this->output_mode;
	}
	
	public function set_output_mode ( $value ) {
		$this->output_mode  =  $value;
	}
}
