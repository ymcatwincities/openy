<?php

/**
 * Google|Bing Map component allows a map to be placed on a page. Locations
 * are defined by uploading a file with either address fields or latitude
 * and longitude coordinates. If address fields are provided, they will be
 * geocoded and the latitude/longitude coordinates saved. Default map marker
 * and shadow images may also be specified.
 *
 * Bing Maps requires an API key obtainable here: http://bingmapsportal.com/
 * The key should be saved as "bing_api_key" in shared_config_settings.
 *
 * @author <jessica@clockwork.net>
 *
 * @package shared
**/

class MapComponent extends Component {

	protected $csv_controller;

	protected $map_data;

	protected $map_type;

	protected $valid_map_types;

	protected $marker_asset_id;

	protected $shadow_asset_id;


	public function __construct ( ) {

		parent::__construct( );

		$this->valid_map_types   =  array( 'google', 'bing' );
		$this->component_type    =  'map';
		$this->_component_keys   =  array(
			'map_type',
			'marker_asset_id',
			'shadow_asset_id',
			'map_data'
		);
		$this->_attributes       =  array(
			'map_type',
			'marker_asset_id',
			'shadow_asset_id'
		);
		$this->_column_mappings  =  array( 'map_data' => 'body' );

		$this->_post_publish_paths[]  =  $GLOBALS['htdocs_directory'] . '/amm/js/google_map.js';
		$this->_post_publish_paths[]  =  $GLOBALS['htdocs_directory'] . '/amm/js/bing_map.js';

		$this->_allowed_actions  =  array( 'display' );
		$this->_default_action   =  'display';

		$this->_js_include_paths  =  array ( 
			'/amm/js/helpful_routines.js',
			'/amm/js/prototype.js',
			'/amm/js/scriptaculous/scriptaculous.js',
			'/amm/js/live_components.js',
		);

		$this->_map_type_js_include_paths  =  array ( 
			'google'  =>  array( 
				'https://maps.googleapis.com/maps/api/js?sensor=false&',
				'/amm/js/google_map.js'
			),
			'bing'  =>  array( 
				'https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1',
				'/amm/js/bing_map.js'
			),
		);

		$this->_input_template_files       =  array( 'map.html' );

		$this->_output_template_files      =  array(
			'google'  =>  'google_map.html',
			'bing'    =>  'bing_map.html',
			'main'    =>  'bing_map.html',
		);

		$this->_input_template_directory   =  $GLOBALS['component_input_template_directory'];

		$this->csv_controller  =  new CSVUploadController( GeolocationFactory::instance( ) );

		// Set validation template
		$xml_dir  =  $GLOBALS['xml_directory'] . '/shared/content/forms/component_inputs';
		$this->csv_controller->set_row_validation_path( $xml_dir . '/map_csv_upload.xml' );
		
		if ( Context::is_development_amm( ) ) {
			$GLOBALS[ 'logger' ]->log( "As of 9/8/2014 use of the map component should be avoided as it does not follow Google's TOS and shares a limited quota with all clients. See components.xml for more info.", CW_LOG_ERROR );
			
		}
	}


	function add_to_validator ( & $form_validator ) {

		$form_data  =  $this->get_form_data( );
		$field_key  =  $this->get_field_key( );

		if ( $form_data[$field_key] === 'download_csv' ) {
			// If we are only downloading the CSV of the locations we don't
			//  need to actually validate all in form fields on the component's
			//  input.
			return;
		}

		return parent::add_to_validator( $form_validator );
	}


	/**
	 * Save component attributes and process file upload if a file is provided.
	 * @todo Make the file upload happen Ajaxically so that the component can
	 * handle itself without redirects. Right now the other attributes are
	 * saved immediately with a microsave - without the redirects, we can't
	 * provide the user with CSV file validation errors/info.
	 *
	 * @param   array  $form_data  
	 * @return  void
	**/
	public function set_up_for_save ( & $form_data ) {

		parent::set_up_for_save( $form_data );

		$field_key  =  $this->get_field_key( );

		if ( $form_data[$field_key] === 'download_csv' ) {
			$this->download_csv( );
			return;
		}

		$field_prefix  =  $this->get_field_prefix( );
		$file_name     =  $field_prefix[0] . 'csv_upload';

		if ( array_get( $_FILES, $file_name ) &&  array_get( $_FILES[$file_name], 'tmp_name' ) ) {

			$this->csv_controller->upload( array_get( $_FILES[$file_name], 'tmp_name' ) );

			$succeeded  =  $this->csv_controller->get_success_count( );
			$failed     =  $this->csv_controller->get_failed_count( );
			$errors     =  $this->csv_controller->get_errors_as_detail_hash( );

			if ( $failed ) {
				$error_message  =  new ErrorMessage( 
					M( 'No data imported.  %s rows had errors.', $failed ), 
					'error', 
					$errors );
				$this->reload_current_page( $error_message );
			}
			else if ( $succeeded ) {

				if ( $succeeded > 500 ) {
					$error_message  =  new ErrorMessage( 
						M( 'No data imported.  The number of locations must not exceed 500.' ),
						'error', 
						$errors );
					$this->reload_current_page( $error_message );
				}

				$map_data  =  $this->process_map_data( $this->csv_controller->get_rows( ) );

				// Records must've been discarded because they were missing an address
				if ( count( $map_data['locations'] ) < $succeeded ) {
					$imported   =  count( $map_data['locations'] );
					$discarded  =  $succeeded - count( $map_data['locations'] );
					$successes  =  array(
						'detail'  =>  "Imported {$imported} record(s)."
						. " Discarded {$discarded} record(s) due to missing"
						. " address or latitude/longitude fields. This can"
						. " also be caused by Google or Bing API errors.",
					);
					$success_message  =  new InfoMessage( 
						M( "Your file imported with warnings." ), 
						"warn", 
						$successes );
				}
				else {
					$successes  =  array(
						'detail'  =>  "Imported {$succeeded} record(s).",
					);
					$success_message  =  new InfoMessage( 
						M( "Your file imported successfully." ), 
						"info", 
						$successes );
				}

				$this->set_map_data( json_encode( $map_data ) );
				// @todo Make the upload happen Ajaxically so that we can let 
				// the component handle itself without redirects.
				$this->_factory->save( $this );

				$this->reload_current_page( $success_message );
			}
			else if ( $errors ) {
				$this->reload_current_page( new ErrorMessage( 
					M( "An error occurred while uploading your file" ), 
					'error', 
					$errors ) );
			}
			else {
				$this->reload_current_page( new WarnMessage(
					M( "No rows were imported." )
				) );
			}
		}
	}


	/**
	 * Populate the component input template. A little bit of validation is
	 * done here on the CSV upload xml definition to make sure the component
	 * finds all of the fields it needs to populate a map. If it doesn't, a
	 * warning is output to the user and file upload is not shown.
	 * 
	 * If locations have been uploaded, a table of data is constructed 
	 * dynamically based on the CSV upload template fields.
	 *
	 * @param   DynamicTemplate  $component_template
	 * @return  DynamicTemplate 
	**/
	public function populate_component_input_template ( DynamicTemplate $component_template ) {

		$component_template  =  parent::populate_component_input_template( $component_template );
		$template_data       =  array( );

		foreach ( $this->valid_map_types as $map_type ) {

			if ( $this->get_map_type( ) === $map_type ) {
				$template_data["map_type_{$map_type}_selected"]  =  'selected';
			}
		}

		$asset_factory      = EntityFactory::get_factory( "AssetFactory" );
		
		if ( $this->marker_asset_id ) {

			$asset = $asset_factory->get( $this->marker_asset_id );
			
			if ( $asset ) {
				$template_data['marker_name']       =  $asset->get_name( );
				$template_data['marker_thumb_url']  =  $asset->get_url(
					'cw_asset_quicklook'
				);
			}
			else {
				$GLOBALS['logger']->log(
					"There was an error retrieving the marker image asset",
					CW_LOG_WARN
				);
			}
		} 

		if ( $this->shadow_asset_id ) {

			$asset = $asset_factory->get( $this->shadow_asset_id );
			
			if ( $asset ) {
				$template_data['shadow_name']       =  $asset->get_name( );
				$template_data['shadow_thumb_url']  =  $asset->get_url(
					'cw_asset_quicklook'
				);
			}
			else {
				$GLOBALS['logger']->log(
					"There was an error retrieving the shadow image asset",
					CW_LOG_WARN
				);
			}
		} 

		// Check on the CSV upload validator and warn the user that their XML
		// file won't work for a map because it's missing required fields 
		$csv_fields      =  $this->csv_controller->get_column_names( );
		$address_fields  =  array_intersect(
			array( 'address1', 'address2', 'city', 'state', 'zip' ),
			$csv_fields
		);
		$lat_lng_fields  =  array_intersect(
			array( 'latitude', 'longitude' ),
			$csv_fields
		);
		$has_address     =  ( count( $address_fields ) == 5 );
		$has_lat_lng     =  ( count( $lat_lng_fields ) == 2 );

		// XML file must at least have address fields to try to geocode or lat/lng 
		if ( ! $has_address && ! $has_lat_lng ) {
			$template_data['bad_xml_file']  =  true;
		}

		$map_data   =  $this->get_map_data_array( );

		if ( ! empty( $map_data ) ) {

			// Used to determine whether to display sample file or saved data
			//  download 
			$template_data['has_map_data']  =  true;

			// Get the header names
			$csv_header  =  $this->csv_controller->get_upload_fields();

			// Set up a template to populate data based on the xml definition
			//  for the CSV file
			$row_template = new DynamicTemplate(
				$this->_input_template_directory . '/map_data.html'
			);
			$row_template->set_soft_rendering();

			// Soft render the header names so that the template always renders
			// out all of its fields without needing a hard-coded template
			$row_template->set_loop_variables( 'header', $csv_header );
			$row_template->set_source( $row_template->render( ) );

			$row_data     =  array( );
			$sample_size  =  0;

			// Now render the actual data with the soft rendered template
			foreach ( $map_data['locations'] as $location ) {

				// Only show a sample size of 10
				if ( $sample_size++ >= 10 ) {
					break;
				}

				// This is for display in the input template
				$location_values  =  array_values( $location );
				$first_column     =  array_shift( $location_values );

				$row_template->set( $location );
				$row_data[] = array(
					'row_class'  =>  'row_'.$sample_size,
					'row_name'   =>  $first_column, 
					'row_data'   =>  $row_template->render( )
				);
			}

			// Add the loops to the component input template
			$component_template->set_loop_variables( 'header', $csv_header );
			$component_template->set_loop_variables( 'data', $row_data );
		}

		$component_template->set( $template_data );

		return $component_template;
	}


	/**
	 * Display action on published side.
	 *
	 * @return  void
	**/
	public function get_component_output ( ) {

		// Change the output template depending on the type of map
		$template  =  $this->get_output_template( $this->map_type  );

		$template_data  =  array( );
		$asset_factory  =  EntityFactory::get_factory( "AssetFactory" );
		$asset          =  $asset_factory->get( $this->marker_asset_id );

		if ( $asset ) {
			$template_data['marker_name']  =  $asset->get_name( );
			$template_data['marker_url']   =  $asset->get_url( 'original' );
		}
		else {
			// @todo: is there a default marker image we should fall back on?
			// This field is required so technically we shouldn't ever get here
			// but it would be nice to have it be optional with a default
			// marker available.
			$GLOBALS['logger']->log( "There was an error retrieving the marker image asset", CW_LOG_WARN );
		}

		// Shadow image asset to go with the marker
		$asset          =  $asset_factory->get( $this->shadow_asset_id );
		
		if ( $asset ) {
			$template_data['shadow_name']  =  $asset->get_name( );
			$template_data['shadow_url']   =  $asset->get_url( 'original' );
		}
		else {
			// @todo: is there a default shadow image we should fall back on?
			$GLOBALS['logger']->log( "There was an error retrieving the shadow image asset", CW_LOG_WARN );
		}

		$map_data  =  $this->get_map_data( );
		if ( ! empty( $map_data ) ) {
			$template_data['has_map_data']  =  true;
		}

		$template_data['map_data']     =  $map_data;
		$api_key_name                  =  "{$this->map_type}_api_key";
		$template_data[$api_key_name]  =  ConfigSetting::get( $api_key_name );

		$template->set( array_merge( $template_data, $this->to_hash( ) ) );
		return $template->render( );
	}


	/**
	 * Process the raw map data imported from the CSV file and geocode if no
	 * latitude and longitude coordinates are provided.  Also compute the
	 * center point and bounding box around all locations.
	 *
	 * @param   array  $map_data
	 * @return  array
	**/
	private function process_map_data ( $map_data ) {

		$geo_factory  =  EntityFactory::get_factory( 'GeolocationFactory' );
		$lats         =  array( );
		$lngs         =  array( );
		$locations    =  array( );

		foreach ( $map_data as & $location ) {

			// If the template contains fields called latitude and longitude, 
			// don't do the address lookup.
			if ( ! array_get( $location, 'latitude' ) && ! array_get( $location, 'longitude' ) ) {
				$address_keys     =  array( 'address1', 'address2', 'city', 'state', 'zip' );
				$full_address     =  $geo_factory->generate_query_from_hash( $location, $address_keys );

				if ( ! $full_address ) {
					continue; 
				}

				$geo  =  $geo_factory->search( $full_address );
				if ( ! $geo ) {
					continue;
				}

				$location['latitude']   =  $geo->get_latitude( );
				$location['longitude']  =  $geo->get_longitude( );
			}

			$locations['locations'][]  =  $location;

			// Save these for figuring out the bounding box
			$lats[]  =  $location['latitude'];
			$lngs[]  =  $location['longitude'];
		}

		// Some points to bubble up to the front end for convenience
		$locations['bounding_box']  =  array( max( $lats ), min( $lngs ), min( $lats ), max( $lngs ) );
		$locations['center_point']  =  array( ( max( $lats ) + min( $lats ) ) / 2, ( max( $lngs ) + min( $lngs ) ) / 2 );

		return $locations;
	}


	/**
	 * Either output the sample CSV file with headers or if data has been
	 * uploaded, prepare a CSV to download the saved data.
	**/
	protected function download_csv ( ) {

		if ( empty( $this->map_data ) ) {
			$filename  =  'sample_locations';
		}
		else {
			$filename  =  'saved_locations_'.date( 'Y-m-d' );
			$this->csv_controller->set_rows(
				array_get( $this->get_map_data_array( ),
					'locations'
				)
			);
		}

		$this->csv_controller->export_file( $filename );
	}

	/**
	 * Reload the current page to permit display of CSV upload errors.
	 * Intended to be used only from set_up_for_save, so should always
	 * redirect to content form.
	 *
	 * @param  string|Message  A string or message to be displayed upon redirect.
	 * @param  array           An array of error details.
	 *
	 * @return void
	 *
	 * @author Lance Erickson <lance@clockwork.net>
	**/
	protected function reload_current_page ( $message ) {
		$current_url  =  Context::request_full_url( );
		redirect( $current_url, $message );
	}

	public function get_map_type ( ) {
		return $this->map_type;
	}

	
	public function set_map_type ( $value ) {
		$this->map_type  =  $value;
	}

	
	public function get_marker_asset_id ( ) {
		return $this->marker_asset_id;
	}


	public function set_marker_asset_id ( $marker_asset_id ) {
		$this->marker_asset_id  =  $marker_asset_id;
	}


	public function get_shadow_asset_id ( ) {
		return $this->shadow_asset_id;
	}


	public function set_shadow_asset_id ( $shadow_asset_id ) {
		$this->shadow_asset_id  =  $shadow_asset_id;
	}

	public function set_map_data ( $map_data ) {
		$this->map_data  =  $map_data;
	}


	public function get_map_data ( ) {
		return $this->map_data;
	}


	public function get_map_data_array ( ) {
		return json_decode( $this->get_map_data( ), $assoc = true );
	}


	/**
	 * Overriden to handle map specific js includes
	**/
	public function get_js_includes ( ) {

		if ( ! isset( $this->_js_include_paths ) ) {
			$this->_js_include_paths  =  array( );
		}

		return array_merge(
			$this->_js_include_paths, 
			$this->_map_type_js_include_paths[ $this->map_type ]
		);
	}
}

