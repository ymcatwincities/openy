<?php

namespace Drupal\Tests\search_api_solr\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_solr_test\Logger\InMemoryLogger;
use Drupal\Tests\search_api\Kernel\BackendTestBase;

/**
 * Tests location searches and distance facets using the Solr search backend.
 *
 * @group search_api_solr
 */
class SearchApiSolrLocationTest extends BackendTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'search_api',
    'search_api_solr',
    'search_api_location',
    'search_api_test_example_content',
    'search_api_solr_test',
    'entity_test',
    'geofield',
    'field',
  ];

  /**
   * A Search API server ID.
   *
   * @var string
   */
  protected $serverId = 'solr_search_server';

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'solr_search_index';

  /**
   * Seconds to wait for a soft commit on Solr.
   *
   * @var int
   */
  protected $waitForCommit = 2;

  /**
   * @var \Drupal\search_api_solr_test\Logger\InMemoryLogger
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig([
      'search_api_solr',
      'search_api_solr_test',
    ]);

    $this->commonSolrBackendSetUp();
  }

  /**
   * Required parts of the setUp() function that are the same for all backends.
   */
  protected function commonSolrBackendSetUp() {
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');

    // Create a location field and storage for testing.
    FieldStorageConfig::create([
      'field_name' => 'location',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'geofield',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev_changed',
      'field_name' => 'location',
      'bundle' => 'item',
    ])->save();

    $this->insertExampleContent();

    /** @var \Drupal\search_api\Entity\Index $index */
    $index = Index::load($this->indexId);

    $location_info = [
      'datasource_id' => 'entity:entity_test_mulrev_changed',
      'property_path' => 'location',
      'type' => 'location',
    ];
    $rpt_info = [
      'datasource_id' => 'entity:entity_test_mulrev_changed',
      'property_path' => 'location',
      'type' => 'rpt',
    ];
    $fieldsHelper = $this->container->get('search_api.fields_helper');

    // Index location coordinates as location data type.
    $index->addField($fieldsHelper->createField($index, 'location', $location_info));

    // Index location coordinates as rpt data type.
    $index->addField($fieldsHelper->createField($index, 'rpt', $rpt_info));

    $index->save();

    /** @var \Drupal\search_api\Entity\Server $server */
    $server = Server::load($this->serverId);

    $config = $server->getBackendConfig();
    $config['retrieve_data'] = TRUE;
    $server->setBackendConfig($config);
    $server->save();

    $this->indexItems($this->indexId);

    $this->logger = new InMemoryLogger();
      /** @var \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel */
    $loggerChannel = \Drupal::service('logger.factory')->get('search_api_solr');
    $loggerChannel->addLogger($this->logger);
  }

  protected function assertLogMessage($level, $message) {
    $last_message = $this->logger->getLastMessage();
    $this->assertEquals($level, $last_message['level']);
    $this->assertEquals($message, $last_message['message']);
  }

  /**
   * Clear the index after every test.
   */
  public function tearDown() {
    $this->clearIndex();
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function indexItems($index_id) {
    $index_status = parent::indexItems($index_id);
    sleep($this->waitForCommit);
    return $index_status;
  }

  /**
   * {@inheritdoc}
   */
  public function insertExampleContent() {
    $this->addTestEntity(1, [
      'name' => 'London',
      'body' => 'London',
      'type' => 'item',
      'location' => 'POINT(-0.076132 51.508530)',
    ]);
    $this->addTestEntity(2, [
      'name' => 'New York',
      'body' => 'New York',
      'type' => 'item',
      'location' => 'POINT(-73.138260 40.792240)',
    ]);
    $this->addTestEntity(3, [
      'name' => 'Brussels',
      'body' => 'Brussels',
      'type' => 'item',
      'location' => 'POINT(4.355607 50.878899)',
    ]);
    $count = \Drupal::entityQuery('entity_test_mulrev_changed')->count()->execute();
    $this->assertEquals(3, $count, "$count items inserted.");
  }

  /**
   * Tests location searches and distance facets.
   */
  public function testBackend() {
    $solr_version = $this->getServer()->getBackend()->getSolrConnector()->getSolrVersion();

    // Search 500km from Antwerp.
    $location_options = [
      [
        'field' => 'location',
        'lat' => '51.260197',
        'lon' => '4.402771',
        'radius' => '500',
      ],
    ];
    /** @var \Drupal\search_api\Query\ResultSet $result */
    $query = $this->buildSearch(NULL, [], NULL, FALSE)
      ->sort('location__distance');

    $query->setOption('search_api_location', $location_options);
    $result = $query->execute();

    $this->assertResults([3, 1], $result, 'Search for 500km from Antwerp ordered by distance');

    /** @var \Drupal\search_api\Item\Item $item */
    $item = $result->getResultItems()['entity:entity_test_mulrev_changed/3:en'];
    $distance = $item->getField('location__distance')->getValues()[0];

    $this->assertEquals(42.5263374675, $distance, 'The distance is correctly returned');

    // Search between 100km and 6000km from Antwerp.
    $location_options = [
      [
        'field' => 'location',
        'lat' => '51.260197',
        'lon' => '4.402771',
      ],
    ];
    $query = $this->buildSearch(NULL, [], NULL, FALSE)
      ->addCondition('location', ['100', '6000'], 'BETWEEN')
      ->sort('location__distance', 'DESC');

    $query->setOption('search_api_location', $location_options);
    $result = $query->execute();

    $this->assertResults([2, 1], $result, 'Search between 100 and 6000km from Antwerp ordered by distance descending');

    $facets_options['location__distance'] = [
      'field' => 'location__distance',
      'limit' => 10,
      'min_count' => 0,
      'missing' => TRUE,
    ];

    // Search 1000km from Antwerp.
    $location_options = [
      [
        'field' => 'location',
        'lat' => '51.260197',
        'lon' => '4.402771',
        'radius' => '1000',
      ],
    ];
    $query = $this->buildSearch(NULL, [], NULL, FALSE)
      ->sort('location__distance');

    $query->setOption('search_api_location', $location_options);
    $query->setOption('search_api_facets', $facets_options);
    $result = $query->execute();
    $facets = $result->getExtraData('search_api_facets', [])['location__distance'];

    $expected = [
      [
        'filter' => '[0 199]',
        'count' => 1,
      ],
      [
        'filter' => '[200 399]',
        'count' => 1,
      ],
      [
        'filter' => '[400 599]',
        'count' => 0,
      ],
      [
        'filter' => '[600 799]',
        'count' => 0,
      ],
      [
        'filter' => '[800 999]',
        'count' => 0,
      ],
    ];

    $this->assertEquals($expected, $facets, 'The correct location facets are returned');

    $facets_options['location__distance'] = [
      'field' => 'location__distance',
      'limit' => 3,
      'min_count' => 1,
      'missing' => TRUE,
    ];

    // Search between 100km and 1000km from Antwerp.
    $location_options = [
      [
        'field' => 'location',
        'lat' => '51.260197',
        'lon' => '4.402771',
        'radius' => '1000',
      ],
    ];

    $query = $this->buildSearch(NULL, [], NULL, FALSE)
      ->addCondition('location', ['100', '1000'], 'BETWEEN')
      ->sort('location__distance');

    $query->setOption('search_api_location', $location_options);
    $query->setOption('search_api_facets', $facets_options);
    $result = $query->execute();

    $facets = $result->getExtraData('search_api_facets', [])['location__distance'];

    $expected = [
      [
        'filter' => '[100 399]',
        'count' => 1,
      ],
    ];

    $this->assertEquals($expected, $facets, 'The correct location facets are returned');

    // Tests the RPT data type of SearchApiSolrBackend.
    $query = $this->buildSearch(NULL, [], NULL, FALSE);
    $options = &$query->getOptions();
    $options['search_api_facets']['rpt'] = [
      'field' => 'rpt',
      'limit' => 3,
      'operator' => 'and',
      'min_count' => 1,
      'missing' => FALSE,
    ];
    $options['search_api_rpt']['rpt'] = [
      'field' => 'rpt',
      'geom' => '["-180 -90" TO "180 90"]',
      'gridLevel' => '2',
      'maxCells' => '35554432',
      'distErrPct' => '',
      'distErr' => '',
      'format' => 'ints2D',
    ];
    $result = $query->execute();
    $expected = [
      [
        'filter' => [
          "gridLevel",
          2,
          "columns",
          32,
          "rows",
          32,
          "minX",
          -180.0,
          "maxX",
          180.0,
          "minY",
          -90.0,
          "maxY",
          90.0,
          "counts_ints2D",
          [NULL, NULL, NULL, NULL, NULL, NULL, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], NULL, [0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL],
        ],
        'count' => 3,
      ],
    ];

    if (version_compare($solr_version, 5.1, '>=')) {
      $facets = $result->getExtraData('search_api_facets', [])['rpt'];
      $this->assertEquals($expected, $facets, 'The correct location facets are returned');
    }
    else {
      $this->assertLogMessage(LOG_ERR, 'Rpt data type feature is only supported by Solr version 5.1 or higher.');
    }

    $query = $this->buildSearch(NULL, [], NULL, FALSE);
    $options = &$query->getOptions();
    $options['search_api_facets']['rpt'] = [
      'field' => 'rpt',
      'limit' => 4,
      'operator' => 'or',
      'min_count' => 1,
      'missing' => FALSE,
    ];
    $options['search_api_rpt']['rpt'] = [
      'field' => 'rpt',
      'geom' => '["-60 -85" TO "130 70"]',
      'gridLevel' => '2',
      'maxCells' => '35554432',
      'distErrPct' => '',
      'distErr' => '',
      'format' => 'ints2D',
    ];
    $result = $query->execute();
    $expected = [
      [
        'filter' => [
          "gridLevel",
          2,
          "columns",
          18,
          "rows",
          29,
          "minX",
          -67.5,
          "maxX",
          135.0,
          "minY",
          -90.0,
          "maxY",
          73.125,
          "counts_ints2D",
          [NULL, NULL, NULL, [0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL],
        ],
        'count' => 2,
      ],
    ];

    if (version_compare($solr_version, 5.1, '>=')) {
      $facets = $result->getExtraData('search_api_facets', [])['rpt'];
      $this->assertEquals($expected, $facets, 'The correct location facets are returned');
    }
    else {
      $this->assertLogMessage(LOG_ERR, 'Rpt data type feature is only supported by Solr version 5.1 or higher.');
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkServerBackend() {}

  /**
   * {@inheritdoc}
   */
  protected function updateIndex() {}

  /**
   * {@inheritdoc}
   */
  protected function checkSecondServer() {}

  /**
   * {@inheritdoc}
   */
  protected function checkModuleUninstall() {}

}
