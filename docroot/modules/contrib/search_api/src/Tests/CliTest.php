<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\CliTest.
 */

namespace Drupal\search_api\Tests;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests Search API functionality when executed in the CLI.
 *
 * @group search_api
 */
class CliTest extends EntityUnitTestBase {

  use ExampleContentTrait;

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('search_api', 'search_api_test_backend');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installSchema('system', 'queue');

    // Create a test server.
    $this->server = Server::create(array(
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    // Manually set the tracking page size since the module's default
    // configuration is not installed automatically in unit tests.
    \Drupal::configFactory()->getEditable('search_api.settings')->set('tracking_page_size', 100)->save();
    // Disable the use of batches for item tracking to simulate a CLI
    // environment.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
  }

  /**
   * Tests tracking of items when saving an index through the CLI.
   */
  public function testItemTracking() {
    $this->setUpExampleStructure();
    $this->insertExampleContent();

    // Create a test index.
    $index = Index::create(array(
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      'status' => 1,
      'datasources' => array('entity:entity_test'),
      'tracker' => 'default',
      'server' => $this->server->id(),
      'options' => array('index_directly' => TRUE),
    ));
    $index->save();

    $total_items = $index->getTracker()->getTotalItemsCount();
    $indexed_items = $index->getTracker()->getIndexedItemsCount();

    $this->assertEqual($total_items, 5, 'The 5 items are tracked.');
    $this->assertEqual($indexed_items, 0, 'No items are indexed');

    $this->insertExampleContent();
    $total_items = $index->getTracker()->getTotalItemsCount();
    $indexed_items = $index->getTracker()->getIndexedItemsCount();

    $this->assertEqual($total_items, 10, 'All 10 items are tracked.');
    $this->assertEqual($indexed_items, 5, '5 items are indexed');
  }

}
