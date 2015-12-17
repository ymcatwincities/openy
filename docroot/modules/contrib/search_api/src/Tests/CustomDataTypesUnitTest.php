<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\CustomDataTypesUnitTest.
 */

namespace Drupal\search_api\Tests;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests custom data types integration.
 *
 * @group search_api
 */
class CustomDataTypesUnitTest extends EntityUnitTestBase {

  use ExampleContentTrait;

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('field', 'search_api', 'search_api_db', 'search_api_test_db', 'search_api_test_backend');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installSchema('system', array('router'));
    $this->installSchema('user', array('users_data'));

    $this->setUpExampleStructure();

    $this->installConfig(array('search_api_test_db'));

    $this->insertExampleContent();

    // Create a test server.
    $this->server = Server::create(array(
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    $this->index = Index::load('database_search_index');
    $this->index->setServer($this->server);
  }

  /**
   * Tests custom data types integration.
   */
  public function testCustomDataTypes() {
    $original_value = $this->entities[1]->get('name')->value;
    $original_type = $this->index->getFields()['entity:entity_test/name']->getType();

    $item = $this->index->loadItem('entity:entity_test/1:en');
    $item = Utility::createItemFromObject($this->index, $item, 'entity:entity_test/1:en');
    $name_field = $item->getField('entity:entity_test/name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEqual($processed_value, $original_value, 'The processed value matches the original value');
    $this->assertEqual($processed_type, $original_type, 'The processed type matches the original type.');

    // Reset the fields on the item and change to the supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $this->index->getFields()['entity:entity_test/name']->setType('search_api_test_data_type');
    $name_field = $item->getField('entity:entity_test/name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEqual($processed_value, $original_value, 'The processed value matches the original value');
    $this->assertEqual($processed_type, 'search_api_test_data_type', 'The processed type matches the new type.');

    // Reset the fields on the item and change to the non-supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $this->index->getFields()['entity:entity_test/name']->setType('search_api_unsupported_test_data_type');
    $name_field = $item->getField('entity:entity_test/name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEqual($processed_value, $original_value, 'The processed value matches the original value');
    $this->assertEqual($processed_type, 'integer', 'The processed type matches the fallback type.');

    // Reset the fields on the item and change to the data altering data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $this->index->getFields()['entity:entity_test/name']->setType('search_api_altering_test_data_type');
    $name_field = $item->getField('entity:entity_test/name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEqual($processed_value, strlen($original_value), 'The processed value matches the altered original value');
    $this->assertEqual($processed_type, 'search_api_altering_test_data_type', 'The processed type matches the defined type.');
  }

}
