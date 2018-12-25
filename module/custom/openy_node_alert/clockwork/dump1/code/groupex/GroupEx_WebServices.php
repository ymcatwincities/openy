<?php
/**
 * General utility functions for GroupEx web services
 *
 * @author Lloyd Dalton   <lloyd@clockwork.net>
 * @author Jessica Zehavi <jessica@clockwork.net>
 * @author Nate Volker    <nate@clockwork.net>
 * @author Jachin Rupe    <jachin@clockwork.net>
 *
**/

load_section( 'custom/groupex' );
load_section( 'amm' );

class GroupEx_WebServices  {

	const DEFAULT_TIMEOUT   =  30;
	const CACHE_NAMESPACE   =  'groupexpro_api_results';
	const CACHE_STATSD_KEY  =  'groupexpro.cache';

	protected $json_url;
	protected $account_id;
	protected $timeout;
	protected $notification_emails;
	protected $use_cache;

	// used to make testing easier
	// see GroupExWebServicesTestCase.php
	protected $RESTRequest_class_name;

	/**
	 * Standard Constructor
	**/
	public function __construct( $RESTRequest_class_name = 'RESTRequest' ) {

		$this->RESTRequest_class_name  =  $RESTRequest_class_name;

		if ( FeatureSwitch::is_enabled( 'groupexpro_kill_switch' ) ) {
			throw new GroupEx_Exception( array( 'message' => 'GroupExPro is disabled' ) );
		}

		$this->json_url    =  $GLOBALS['config']['custom/groupex']['groupex_json_url'];
		$this->account_id  =  $GLOBALS['config']['custom/groupex']['groupex_account_id'];

		// Get the timeout value set in custom/groupex/config.php or 30 sec if
		// it's not set for some reason.
		$this->timeout  =  array_get(
			$GLOBALS['config']['custom/groupex'],
			'groupex_timeout', self::DEFAULT_TIMEOUT
		);

		// Get the comma separated list of notification e-mails that should be
		// set in custom/groupex/config.local.php or fall back on the error email
		$this->notification_emails  =  array_get(
			$GLOBALS['config']['custom/groupex'],
			'groupex_notification_emails', $GLOBALS['error_email']
		);

		$this->use_cache  =  array_get( $GLOBALS['config']['custom/groupex'],
			'use_cache'
		);
	}


	/**
	 * Sets whether or not to use the cache
	**/
	public function set_use_cache ( $use_cache ) {
		$this->use_cache  =  $use_cache ? true : false;
	}


	/**
	 * Fetch the locations from GroupExPro (or the cache)
	 *
 	 * @return array Array used to populate template
	**/
	public function get_locations ( ) {
		return $this->_get_data( "locations" );
	}


	/**
	 * Fetch the categories from GroupExPro (or the cache)
	 *
 	 * @return array Array used to populate template
	**/
	public function get_categories ( ) {
		return $this->_get_data( "categories" );
	}


	/**
	 * Prune categories that are not explicitly included in the component
	 * input so that they do not appear in the dropdown in the output form.
	 *
	 * @author Jessica Zehavi <jessica@clockwork.net>
	 *
	 * @return array              Array containing only included categories
	**/
	public function get_pruned_categories ( array $allowed_categories=[ ] ) {

		if ( ! $allowed_categories ) {

			$allowed_categories  =  ConfigSetting::get( "groupex_categories" );

			if ( ! is_array( $allowed_categories ) ) {
				$allowed_categories  =  array( );
			}
		}

		$categories  =  array_filter( $this->_get_data( "categories" ),
			function ( $category ) use ( $allowed_categories ) {

				// If the category is not in the list of allowed categories,
				// remove it from the array so that it cannot be searched
				if ( ! in_array( $category['id'], $allowed_categories ) ) {
					return false;
				}

				// Otherwise, we'll keep it
				return true;
		} );

		// DT gets confused when arrays don't appear to be numerically
		// indexed. Excluding certain categories removes them from the
		// array without reordering so array_values gets called to just
		// do the reordering for us.
		return array_values( $categories );
	}


	/**
	 * Fetch the classes from GroupExPro (or the cache)
	 *
	 * @param array allowed_category_ids Default null. If an array is not provided
	 *                                   this will be looked up. This parameter
	 *                                   is helpful for unit testing.
	 * @param array excluded_class_ids Default null. if an array is not provided
	 *                                 this will be looked up. This parameter
	 *                                 is helpful for unit testing.
	 * @return array Array used to populate template
	**/
	public function get_classes (
		array $allowed_category_ids=null,
		array $excluded_class_ids=null
	) {

		if ( ! is_array( $allowed_category_ids ) ) {
			// allowed_category_ids not passed in, lets look them up.
			$allowed_categories  =  $this->get_pruned_categories( );
			$allowed_category_ids  =  array( );

			foreach ( $allowed_categories as $category ) {
				$allowed_category_ids[]  =  $category['id'];
			}
		}

		if ( ! is_array( $excluded_class_ids ) ) {
			$excluded_class_ids  =  $this->get_excluded_class_ids(
				$allowed_category_ids
			);
		}

		if ( $excluded_class_ids ) {
			$classes  =  $this->_get_data(
				"classes",
				array( //params
					'exclude'  =>  $excluded_class_ids,
				)
			);
		}
		else {
			$classes  =  $this->_get_data( "classes" );
		}

		return $classes;
	}


	/**
	 * Fetch the excluded class IDs
	 *
	 * @param array $allowed_category_ids Default empty array. This parameter
	 *                                    is useful for unit testing.
	 *
	 * @return array Array of integers (class IDs)
	**/
	public function get_excluded_class_ids ( array $allowed_category_ids=[ ] ) {

		$all_categories       =  $this->get_categories( );
		$excluded_categories  =  array( );
		$excluded_class_ids   =  array( );

		$allowed_categories_dict  =  array( );

		foreach ( $all_categories as $c ) {
			if ( ! in_array( $c['id'], $allowed_category_ids ) ) {
				$excluded_categories[$c['id']]  =  $c['name'];
			}
		}

		$excluded_category_ids  =  array_keys( $excluded_categories );

		if ( empty( $excluded_category_ids ) ) {
			return array( );
		}

		$process_category_classes  =  function ( $raw_data, $params ) {
			$class_ids  =  array( );

			foreach ( $raw_data as $category_id => $category_class_ids ) {
				foreach ( $category_class_ids as $category_class_id ) {
					$class_ids[]  =  $category_class_id;
				}
			}

			return $class_ids;
		};

		$excluded_class_ids  =  $this->_get_data(
			'categoryClasses',
			array( 'category'  =>  $excluded_category_ids ),
			false,
			$process_category_classes
		);

		return $excluded_class_ids;
	}


	/**
	 * Fetch the schedule from GroupExPro (or the cache)
	 *
	 * @param  array $form_data  Form data with search params
	 * @return array             Array used to populate template
	**/
	public function get_schedule ( $form_data ) {
		$params  =  array_merge( array( 'desc' => 'true' ), $form_data );
		return $this->_get_data( "schedule", $params );
	}


	/**
	 * Fetch schedules, broken down by location
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 *
	 * @param   array  $form_data  Form data with params
	 * @return  array              Array used to populate template
	**/

	public function get_schedule_by_location ( $form_data ) {

		$results   =  $this->get_schedule( $form_data );
		$schedule  =  $results['schedule'];

		foreach ( $schedule as $day_index => $day ) {

			$classes_by_location  =  array( );

			foreach ( $day['schedule_day'] as $class_session ) {
				$location                          =  $class_session['location'];
				$classes_by_location[$location][]  =  $class_session;
			}

			foreach ( $classes_by_location as $location_name => $classes ) {
				$results['schedule'][$day_index]['locations'][]  =  array(
					'location_name'      =>  $location_name,
					'classes'            =>  $classes,
					'day_' . $day_index  =>  true
				);
			}
		}

		return $results;
	}


	/**
	 * Handle an exception that needs to send an e-mail and possibly be thrown
	 * as a GroupEx_Exception. RESTRequestException are caught and handled
	 * here.  Only GroupEx_Exceptions are handled in the component.
	 *
	 * @author Jessica Zehavi <jessica@clockwork.net>
	 *
	 * @param   array  $detail  Associative array of error details
	 * @return  void
	**/

	public function handle_service_exception ( $detail ) {
		$e  =  new GroupEx_Exception( $detail );
		$this->send_service_notification( $e );
		throw $e;
	}


	/**
	 * Send a service notification e-mail to people that care (defined in
	 * custom/groupex/config.local.php). Converts the associative array of
	 * error details into something iterable for DT because the
	 * exception may contain different error details.
	 *
	 * @author Jessica Zehavi <jessica@clockwork.net>
	 *
	 * @param   Exception  $e  Exception that was thrown
	 * @return  boolean        true if email was sent successful, false if not
	**/

	public function send_service_notification ( Exception $e ) {

		$to          =  $this->notification_emails;
		$from        =  $GLOBALS['config']['custom/groupex']['groupex_notification_email_from'];
		$subject     =  'GroupExPro Service Notification';

		$email_template  =  new DynamicTemplate(
			$GLOBALS['template_directory']
			. '/email/custom/groupex/groupex_service_notification.txt'
		);

		$error_hash    =  $e->to_display_hash( );
		$error_detail  =  array( );

		// to_display_hash will return an associative array of key => value
		// pairs but since we will not consistently get the same fields, turn
		// it into something we can use in a loop instead
		foreach ( $error_hash as $key => $value ) {
			$error_detail[]  =  array( 'key'    => $key, 'value'  => $value );
		}

		$site  =  get_site( Context::get_site_id( ) );
		$email_template->set( array( 'site'  => $site['domain_name']) );
		$email_template->set_loop_variables( 'detail', $error_detail );

		if ( ! send_email( $to, $from, $subject, $email_template->render( ) ) ) {
			// If sending the e-mail was unsuccessful, at least do a LOG_ALERT
			// so that someone gets an e-mail
			$GLOBALS['logger']->log(
				"Error sending GroupExPro service notification failure e-mail"
				. " for the following error: "
				. var_export( $error_hash, true ), CW_LOG_ALERT );
			return false;
		}
		else {
			// Log as a warning since an e-mail was sent
			$GLOBALS['logger']->log( "A GroupExPro error occurred: "
			       . var_export( $error_hash, true ), CW_LOG_WARN );
			return true;
		}
	}


	/**
	 * Default method to processes the JSON-decoded data that we received
	 * from the API call to GroupExPro.
	 *
	 * @author Nate Volker <nate@clockwork.net>
	 *
	 * @param   array[]  $raw_data  data received from the API call (an array of arrays)
	 * @param   array    $keys      array keys that we want to keep in the result
	 * @return  array               Array used to populate template
	**/

	protected function _process_response( array $raw_data, array $keys ) {

		$processed_data  =  array( );

		foreach( $raw_data as $array_element ) {

			$inner_array  =  array( );

			foreach( $keys as $key ) {
				$inner_array[$key]  =  trim( $array_element[$key] );
			}

			$processed_data[]  =  $inner_array;

		}

		return HelpfulRoutines::strip_tags_all( $processed_data );
	}


	/**
	 * Processes the JSON-decoded classes that we received.
	 * from the API call to GroupExPro
	 *
	 * @author Nate Volker <nate@clockwork.net>
	 *
	 * @param   array  $raw_classes  data received from the API call
	 * @param   array  $form_data    Form data with search params
	 * @return  array                Array used to populate template
	**/

	protected function _process_classes (
		array $raw_classes,
		array $form_data = array( )
	) {

		$classes  =  $this->_process_response(
			$raw_classes,
			array( 'id', 'title', 'description' )
		);

		// strip out the junk from the 'id' field
		foreach( $classes as &$class ) {
			$class['id']  =  preg_replace( '/\D+/', '', $class['id'] );
		}

		return $classes;
	}


	/**
	 * Processes the JSON-decoded schedule that we received
	 * from the API call to GroupExPro
	 *
	 * @author Lloyd Dalton <lloyd@clockwork.net>
	 * @author Nate Volker <nate@clockwork.net>
	 *
	 * @param   array  $raw_schedules  data received from the API call
	 * @param   array  $form_data      Form data with search params
	 * @return  array                  Array used to populate template
	**/

	protected function _process_schedule ( array $raw_schedules, array $form_data = array( ) ) {

		// Start the loop with a date so we can build the template variables
		$date  =  $raw_schedules[0]['date'];

		$schedules  =  array( );
		//$classes    =  $this->get_classes( );
		$n_classes  =  0;
		$n_days     =  0;

		foreach( $raw_schedules as $schedule ) {

			$time  =  explode( '-', $schedule['time'] );

			try {

				$start_timestamp_datetime  =  new DateTime( $time[0] );
				$start_timestamp_hour      =  date_format( $start_timestamp_datetime, 'G' );

				$time_of_day  =  "morning";
				$time_of_day  =  ( $start_timestamp_hour >= 12 ) ? "afternoon" : $time_of_day;
				$time_of_day  =  ( $start_timestamp_hour >= 17 ) ? "evening"   : $time_of_day;

				if ( array_key_exists( 'time_filter', $form_data ) ) {
					if ( ! $form_data[$time_of_day] ) {
						continue;
					}
				}
			}
			catch ( Exception $e ) {

				$GLOBALS['logger']->log(
					"Invalid time set for schedule with id"
					. " \"{$schedule['id']}\" ({$schedule['time']})\n"
					. "Exception thrown with message: {$e->getMessage}",
					CW_LOG_ERROR
				);

				if ( array_key_exists( 'time_filter', $form_data ) ) {
					continue;
				}

				$time_of_day  =  "All Day";
				$time[0]      =  "12:00am";
				$time[1]      =  "11:59pm";
			}

			// New day
			if ( $date !== $schedule['date'] ) {
				$date  =  $schedule['date'];
				++$n_days;
			}

			$schedule['desc']  =  str_replace(
				array( "<br>", "<br />", "<br/>" ),
				" ",
				$schedule['desc']
			);

			$schedules[$n_days]['day']             =  $schedule['date'];
			$schedules[$n_days]['schedule_day'][]  =  array(
				'id'           =>  $schedule['id'],
				'location'     =>  trim( $schedule['location'] ),
				'length'       =>  $schedule['length'],
				'instructor'   =>  trim( $schedule['instructor'] ),
				'category'     =>  trim( $schedule['category'] ),
				'studio'       =>  trim( $schedule['studio'] ),
				'title'        =>  trim( $schedule['title'] ),
				'description'  =>  trim( $schedule['desc'] ),
				'start'        =>  $time[0],
				'end'          =>  $time[1],
				'time_of_day'  =>  $time_of_day,
				'date'         =>  $schedule['date']
			);
			++$n_classes;
		}

		// Strip out HTML tags
		$schedules  =  HelpfulRoutines::strip_tags_all( $schedules );

		$results  =  array( 'n_classes' => $n_classes, 'schedule' => $schedules );

		return $results;
	}


	/**
	 * Gets data from the provided type and parameters (from the cache if possible)
	 * Caches the result in MEMCACHED if needed.
	 *
	 * @author Nate Volker <nate@clockwork.net>
	 *
	 * @param   string  $type      The type of data we're requesting
	 * @param   array   $params    URL parameters to pass along in the request
	 * @param   bool    $use_post  If true, sends a POST request, GET otherwise
	 * @param   function $data_processor An optional function for processing
	 *                                   the data before returning it.
	 * @return  array              The response
	**/

	protected function _get_data( $type, $params=[], $use_post=false, $data_processor=null ) {

		// add the account id and type parameters
		$params['a']    =  array_get( $params, 'a', $this->account_id );
		$params[$type]  =  array_get( $params, $type, '' );
		$url            =  URLs::append_parameters( $this->json_url, $params );

		if ( strlen( $url ) > 4096 ) {
			throw new UnexpectedValueException(
				"The GroupEx Web Service only accepts URLs up to 4096"
				. " characters long: {$url}"
			);
		}

		if ( $this->use_cache ) {
			// check the cache first
			$cache  =  new CacheStorage(
				self::CACHE_NAMESPACE,
				CacheStorage::STORAGE_MEMCACHED,
				( CacheStorage::TIME_ONE_HOUR / 4 ) // Cache for 15 minutes
			);

			$cache_key  =  $url;
			$cached     =  $cache->load( $cache_key );

			if ( $cached ) {
				StatsD::increment( self::CACHE_STATSD_KEY . ".{$type}.hit" );
				return $cached;
			}

			// result wasn't cached, make the request to the GroupExPro API
			StatsD::increment( self::CACHE_STATSD_KEY . ".{$type}.miss" );
		}

		$req  =  new $this->RESTRequest_class_name( $url );
		$req->set_timeout( $this->timeout );
		$req->set_use_post( $use_post );

		try {
			$rsp  =  $req->request( );
		}
		catch ( RESTRequestException $e ) {

			$this->handle_service_exception( $e->to_display_hash( ) );
		}

		$raw_data  =  json_decode( $rsp->get_response_text( ), true );

		if ( ! is_array( $raw_data ) ) {
			$error_detail  =  array(
				'Message'   =>  "No {$type} retrieved from GroupExPro.",
				'Response'  =>  $raw_data,
				'URL'       =>  $url
			);
			$this->handle_service_exception( $error_detail );
			return;
		}

		// process the response
		$method_name  =  "_process_{$type}";

		if ( $data_processor ) {
			$processed_data  =  $data_processor( $raw_data, $params );
		}

		else if ( method_exists( $this, $method_name ) ) {
			$processed_data  =  $this->$method_name( $raw_data, $params );
		}
		else {
			$processed_data  =  $this->_process_response(
				$raw_data,
				array( 'id', 'name' )
			);
		}

		// save the processed response to the cache
		if ( $this->use_cache ) {
			$cache->save( $url, $processed_data );
		}

		return $processed_data;
	}
}


class GroupEx_Exception extends Exception {

	protected $detail;

	public function __construct ( array $detail ) {
		$this->detail  =  $detail;
		parent::__construct( print_r( $detail, true ) );
	}

	public function to_display_hash ( ) {
		return $this->detail;
	}
}
