<?php

/**
 * Unit test case for GroupExPro Web Services
 *
 * @author Nate Volker <nate@clockwork.net>
**/

class GroupEx_WebServicesTestCase extends \Cw\Shared\Testing\SimpleTestCompatibleUnitTestCase {

	protected $group_ex_web_sevices;

	public function __construct( ) {

		parent::__construct( );
		$this->group_ex_web_sevices  =  new GroupEx_WebServices( 'GroupEx_WebServicesMockRESTRequest' );
		$this->group_ex_web_sevices->set_use_cache( false );

	}

	public static function setUpBeforeClass ( ) {
		self::skip_if_not_product( 'custom/groupex' );
	}


	// === Test Functions ===

	public function test_get_locations ( ) {

		$locations  =  $this->group_ex_web_sevices->get_locations( );
		$expected  =  array(
			array(
				'id'   =>  26,
				'name' =>  'Andover',
			),
			array(
				'id'   =>  33,
				'name' =>  'Blaisdell'
			),
			array(
				'id'   =>  30,
				'name' =>  'Burnsville'
			),
		);

		foreach( $expected as $index => $location ) {
			$this->assertEqual(
				$location['id'],
				$locations[$index]['id'],
				"Location ID returned does not match expected ID ({$location['id']} != {$locations[$index]['id']})"
			);
			$this->assertEqual(
				$location['name'],
				$locations[$index]['name'],
				"Location name returned does not match expected name (\"{$location['name']}\" != \"{$locations[$index]['name']}\")"
			);
		}

	}


	public function test_get_categories ( ) {

		$categories  =  $this->group_ex_web_sevices->get_categories( );
		$expected  =  array(
			array(
				'id'   =>  407,
				'name' =>  '55+',
			),
			array(
				'id'   =>  410,
				'name' =>  'Cardio'
			),
			array(
				'id'   =>  408,
				'name' =>  'Cardio & Strength Combo'
			),
		);

		foreach( $expected as $index => $category ) {
			$this->assertEqual(
				$category['id'],
				$categories[$index]['id'],
				"Category ID returned does not match expected ID ({$category['id']} != {$categories[$index]['id']})"
			);
			$this->assertEqual(
				$category['name'],
				$categories[$index]['name'],
				"Category name returned does not match expected name (\"{$category['name']}\" != \"{$categories[$index]['name']}\")"
			);
		}

	}


	public function test_get_classes ( ) {
		$classes  =  $this->group_ex_web_sevices->get_classes(
			array( ), // allowed_cateogry_ids
			array( )  // excluded_class_ids
		);
		$expected  =  array(
			array(
				'id'          =>  121,
				'title'       =>  'AOA Aerobics',
				'description' =>  'Fake Description 1',
			),
			array(
				'id'          =>  1335,
				'title'       =>  'AOA Cycle',
				'description' =>  'Fake Description 2',
			),
			array(
				'id'          =>  128,
				'title'       =>  'AOA Fitness Yoga',
				'description' =>  'Fake Description 3&nbsp;&nbsp;',
			),
		);

		foreach( $expected as $index => $class ) {
			$this->assertEqual(
				$class['id'],
				$classes[$index]['id'],
				"Class ID returned does not match expected ID ({$class['id']} != {$classes[$index]['id']})"
			);
			$this->assertEqual(
				$class['title'],
				$classes[$index]['title'],
				"Class title returned does not match expected title (\"{$class['title']}\" != \"{$classes[$index]['title']}\")"
			);
			$this->assertEqual(
				$class['description'],
				$classes[$index]['description'],
				"Class description returned does not match expected description (\"{$class['description']}\" != \"{$classes[$index]['description']}\")"
			);
		}
	}


	public function test_get_pruned_categories( ) {
		$pruned_categories  =  $this->group_ex_web_sevices->get_pruned_categories(
			array( //allowed categories
				'408', '410'
			)
		);

		$this->assertInternalType( 'array', $pruned_categories );
		$this->assertCount( 2, $pruned_categories );

		$pruned_categories  =  $this->group_ex_web_sevices->get_pruned_categories(
			array( ) //allowed categories
		);

		$this->assertInternalType( 'array', $pruned_categories );
		$this->assertCount( 0, $pruned_categories );
	}


	public function test_get_schedule ( ) {
		$params  =  array(
			'location'  =>  array( 17, 28, 14, 13 ),
			'start'     =>  1365656400,
			'end'       =>  1365656400,
		);

		$schedule  =  $this->group_ex_web_sevices->get_schedule( $params );

		$expected  =  array(
			array(
				"id" => 40235,
				"location" => 'Hudson, WI',
				"length" => 60,
				"instructor" => 'Ann P',
				"category" => 'Strength',
				"studio" => 'Studio GX',
				"title" => 'BodyPump®',
				"description" => 'Test Description 1&trade;&nbsp;',
				"start" => '5:10am',
				"end" => '6:10am',
				"time_of_day" => 'morning',
				"date" => 'Thursday, April 11, 2013',
			),
			array(
				"id" => 38829,
				"location" => 'Woodbury' ,
				"length" => 60,
				"instructor" => 'Kathy A',
				"category" => 'Strength',
				"studio" => 'Studio 3',
				"title" => 'BodyPump®',
				"description" => 'Test Description 2&trade;&nbsp;',
				"start" => '5:30am',
				"end" => '6:30am',
				"time_of_day" => 'morning',
				"date" => 'Thursday, April 11, 2013',
			),
			array(
				"id" => 115211,
				"location" => 'St. Paul Downtown',
				"length" => 45,
				"instructor" => 'Angie O',
				"category" => 'Cardio',
				"studio" => 'Skyway Studio',
				"title" => 'Group Cycle',
				"description" => 'Test Description 3&nbsp;&nbsp;',
				"start" => '12:00am',
				"end" => '11:59pm',
				"time_of_day" => 'All Day',
				"date" => 'Thursday, April 11, 2013',
				)
			);

		$this->assertEqual(
			count( $expected ),
			$schedule['n_classes'],
			"Number of classes returned does not match expected ({$schedule['n_classes']} != " . count( $expected ) . ")"
		);

		$test_keys  =  array(
			"id",
			"location",
			"length",
			"instructor",
			"category",
			"studio",
			"title",
			"description",
			"start",
			"end",
			"time_of_day",
			"date"
		);

		foreach( $expected as $index => $class ) {
			foreach( $test_keys as $key ) {
				$this->assertEqual(
					$class[$key],
					$schedule['schedule'][0]['schedule_day'][$index][$key],
					"Schedule {$key} returned does not match expected {$key} ({$class[$key]} != {$schedule['schedule'][0]['schedule_day'][$index][$key]})"
				);
			}
		}

	}


	public function test_get_schedule_by_location ( ) {

		$params  =  array(
			'location'  =>  array( 17, 28, 14, 13 ),
			'start'     =>  1365656400,
			'end'       =>  1365656400,
		);

		$schedule   =  $this->group_ex_web_sevices->get_schedule_by_location( $params );
		$locations  =  $schedule['schedule'][0]['locations'];

		$expected  =  array(
			array(
				"location_name" => 'Hudson, WI',
				"classes" => array(
					array(
						"id" => 40235,
						"location" => 'Hudson, WI',
						"length" => 60,
						"instructor" => 'Ann P',
						"category" => 'Strength',
						"studio" => 'Studio GX',
						"title" => 'BodyPump®',
						"description" => 'Test Description 1&trade;&nbsp;',
						"start" => '5:10am',
						"end" => '6:10am',
						"time_of_day" => 'morning',
						"date" => 'Thursday, April 11, 2013',
					)
				)
			),
			array(
				"location_name" => 'Woodbury',
				"classes" => array(
					array(
						"id" => 38829,
						"location" => 'Woodbury',
						"length" => 60,
						"instructor" => 'Kathy A',
						"category" => 'Strength',
						"studio" => 'Studio 3',
						"title" => 'BodyPump®',
						"description" => 'Test Description 2&trade;&nbsp;',
						"start" => '5:30am',
						"end" => '6:30am',
						"time_of_day" => 'morning',
						"date" => 'Thursday, April 11, 2013',
					)
				)
			),
			array(
				"location_name" => 'St. Paul Downtown',
				"classes" => array(
					array(
						"id" => 115211,
						"location" => 'St. Paul Downtown',
						"length" => 45,
						"instructor" => 'Angie O',
						"category" => 'Cardio',
						"studio" => 'Skyway Studio',
						"title" => 'Group Cycle',
						"description" => 'Test Description 3&nbsp;&nbsp;',
						"start" => '12:00am',
						"end" => '11:59pm',
						"time_of_day" => 'All Day',
						"date" => 'Thursday, April 11, 2013',
					)
				)
			)
		);

		$test_keys  =  array(
			"id",
			"location",
			"length",
			"instructor",
			"category",
			"studio",
			"title",
			"description",
			"start",
			"end",
			"time_of_day",
			"date"
		);

		foreach( $expected as $index => $expected_location ) {

			$this->assertEqual(
				$expected_location['location_name'],
				$locations[$index]['location_name'],
				"Location name returned does not match expected location name ({$expected_location['location_name']} != {$locations[$index]['location_name']})"
			);

			foreach( $test_keys as $key ) {
				$this->assertEqual(
					$expected_location['classes'][0][$key],
					$locations[$index]['classes'][0][$key],
					"Location {$expected_location['location_name']}, {$key} returned does not match expected {$key} ({$expected_location['classes'][0][$key]} != {$locations[$index]['classes'][0][$key]})"
				);
		 	}
		}
	}
}


class GroupEx_WebServicesMockRESTRequest {

	protected $_url;

	public function __construct ( $url ) {
		$this->_url  =  $url;
	}

	public function set_timeout ( $timeout ) { }
	public function set_use_post ( $use_post ) { }

	public function request( ) {

		$url  =  $GLOBALS['config']['custom/groupex']['groupex_json_url'];

		switch( $this->_url ) {

			case "{$url}?a=190&locations=":
				$response_text  =  '[' .
					'{"id":"26","name":"Andover "},' .
					'{"id":"33","name":"Blaisdell "},' .
					'{"id":"30","name":"Burnsville "}' .
					']';
				break;

			case "{$url}?a=190&categories=":
				$response_text  =  '[' .
					'{"id":"407","name":"55+"},' .
					'{"id":"410","name":"Cardio "},' .
					'{"id":"408","name":"Cardio & Strength Combo"}' .
					']';
				break;

			case "{$url}?a=190&classes=":
				$response_text  =  '[' .
					'{"id":"DESC--[121","title":"AOA Aerobics","description":"<p>Fake Description 1</p>"},' .
					'{"id":"DESC--[1335","title":"AOA Cycle","description":"<p>Fake Description <span>2</span></p>"},' .
					'{"id":"DESC--[128","title":"AOA Fitness Yoga","description":"<p>Fake Description 3&nbsp;<span _fck_bookmark=\"1\" style=\"display: none\">&nbsp;</span></p>"}' .
					']';
				break;

			case "{$url}?desc=true&location%5B0%5D=17&location%5B1%5D=28&location%5B2%5D=14&location%5B3%5D=13&start=1365656400&end=1365656400&a=190&schedule=":
				$response_text  =  '[
					{
						"date":"Thursday, April 11, 2013",
						"time":"5:10am-6:10am",
						"title":"BodyPump®",
						"studio":"Studio GX",
						"category":"Strength",
						"instructor":"Ann P",
						"original_instructor":"Ann P",
						"sub_instructor":"",
						"length":"60",
						"location":"Hudson, WI",
						"id":"40235",
						"desc":"<p>Test Description 1&trade;<span _fck_bookmark=\"1\" style=\"display: none\">&nbsp;</span></p>"
					},
					{
						"date":"Thursday, April 11, 2013",
						"time":"5:30am-6:30am",
						"title":"BodyPump®",
						"studio":"Studio 3",
						"category":"Strength",
						"instructor":"Kathy A",
						"original_instructor":"Kathy A",
						"sub_instructor":"",
						"length":"60",
						"location":"Woodbury ",
						"id":"38829",
						"desc":"<p>Test Description 2&trade;<span _fck_bookmark=\"1\" style=\"display: none\">&nbsp;</span></p>"
					},
					{
						"date":"Thursday, April 11, 2013",
						"time":"All Day",
						"title":"Group Cycle",
						"studio":"Skyway Studio",
						"category":"Cardio ",
						"instructor":"Angie O",
						"original_instructor":"Angie O",
						"sub_instructor":"",
						"length":"45",
						"location":"St. Paul Downtown",
						"id":"115211",
						"desc":"<p>Test Description 3&nbsp;<span _fck_bookmark=\"1\" style=\"display: none\">&nbsp;</span></p>"
					}
				]';
				break;

		}

		return new GroupEx_WebServicesMockRESTResponse( $response_text );
	}
}


class GroupEx_WebServicesMockRESTResponse {

	protected $_response_text;

	public function __construct ( $response_text ) {
		$this->_response_text  =  $response_text;
	}

	public function get_response_text ( ) {
		return $this->_response_text;
	}
}
