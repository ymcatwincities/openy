<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\LanguageIntegrationUnitTest.
 */

namespace Drupal\search_api\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility;
use Drupal\system\Tests\Entity\EntityLanguageTestBase;

/**
 * Tests translation handling of the content entity datasource.
 *
 * @group search_api
 */
class LanguageIntegrationUnitTest extends EntityLanguageTestBase {

  /**
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mul';

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
  public static $modules = array('search_api', 'search_api_test_backend');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));

    // Create a test server.
    $this->server = Server::create(array(
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    // Create a test index.
    $this->index = Index::create(array(
      'name' => $this->randomString(),
      'id' => $this->randomMachineName(),
      'status' => 1,
      'datasources' => array('entity:' . $this->testEntityTypeId),
      'tracker' => 'default',
      'server' => $this->server->id(),
      'options' => array('index_directly' => FALSE),
    ));
    $this->index->save();

    Utility::getIndexTaskManager()->addItemsAll($this->index);
  }

  /**
   * Tests translation handling of the content entity datasource.
   */
  public function testItemTranslations() {
    // Test retrieving language and translations when no translations are
    // available.
    $entity_1 = EntityTestMul::create(array(
      'id' => 1,
      'name' => 'test 1',
      'user_id' => $this->container->get('current_user')->id(),
    ));
    $entity_1->save();
    $this->assertEqual($entity_1->language()->getId(), 'en', new FormattableMarkup('%entity_type: Entity language set to site default.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    $entity_2 = EntityTestMul::create(array(
      'id' => 2,
      'name' => 'test 2',
      'user_id' => $this->container->get('current_user')->id(),
    ));
    $entity_2->save();
    $this->assertEqual($entity_2->language()->getId(), 'en', new FormattableMarkup('%entity_type: Entity language set to site default.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_2->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    // Test that the datasource returns the correct item IDs.
    $datasource = $this->index->getDatasource('entity:' . $this->testEntityTypeId);
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:en',
      '2:en',
    );
    $this->assertEqual($datasource_item_ids, $expected, 'Datasource returns correct item ids.');

    // Test indexing the new entity.
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 0, 'The index is empty.');
    $this->assertEqual($this->index->getTracker()->getTotalItemsCount(), 2, 'There are two items to be indexed.');
    $this->index->index();
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 2, 'Two items have been indexed.');

    // Now, make the first entity language-specific by assigning a language.
    $default_langcode = $this->langcodes[0];
    $entity_1->get('langcode')->setValue($default_langcode);
    $entity_1->save();
    $this->assertEqual($entity_1->language(), \Drupal::languageManager()->getLanguage($this->langcodes[0]), new FormattableMarkup('%entity_type: Entity language retrieved.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    // Test that the datasource returns the correct item IDs.
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '2:en',
    );
    $this->assertEqual($datasource_item_ids, $expected, 'Datasource returns correct item ids.');

    // Test that the index needs to be updated.
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 1, 'The updated item needs to be reindexed.');
    $this->assertEqual($this->index->getTracker()->getTotalItemsCount(), 2, 'There are two items in total.');

    // Set two translations for the first entity and test that the datasource
    // returns three separate item IDs, one for each translation.
    $translation = $entity_1->addTranslation($this->langcodes[1]);
    $translation->save();
    $translation = $entity_1->addTranslation($this->langcodes[2]);
    $translation->save();
    $this->assertTrue($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: Translations are available', array('%entity_type' => $this->testEntityTypeId)));

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '1:' . $this->langcodes[2],
      '2:en',
    );
    $this->assertEqual($datasource_item_ids, $expected, 'Datasource returns correct item ids for a translated entity.');

    // Test that the index needs to be updated.
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 1, 'The updated items needs to be reindexed.');
    $this->assertEqual($this->index->getTracker()->getTotalItemsCount(), 4, 'There are four items in total.');

    // Delete one translation and test that the datasource returns only three
    // items.
    $entity_1->removeTranslation($this->langcodes[2]);
    $entity_1->save();

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '2:en',
    );
    $this->assertEqual($datasource_item_ids, $expected, 'Datasource returns correct item ids for a translated entity.');

    // Test reindexing.
    $this->assertEqual($this->index->getTracker()->getTotalItemsCount(), 3, 'There are three items in total.');
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 1, 'The updated items needs to be reindexed.');
    $this->index->index();
    $this->assertEqual($this->index->getTracker()->getIndexedItemsCount(), 3, 'Three items are indexed.');
  }

}
