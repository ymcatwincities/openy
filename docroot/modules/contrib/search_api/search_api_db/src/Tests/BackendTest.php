<?php

/**
 * @file
 * Contains \Drupal\search_api_db\Tests\BackendTest.
 */

namespace Drupal\search_api_db\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Utility;
use Drupal\search_api_db\Plugin\search_api\backend\Database as BackendDatabase;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests index and search capabilities using the Database search backend.
 *
 * @group search_api
 */
class BackendTest extends EntityUnitTestBase {

  use ExampleContentTrait;
  use StringTranslationTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('field', 'search_api', 'search_api_db', 'search_api_test_db');

  /**
   * A search server ID.
   *
   * @var string
   */
  protected $serverId = 'database_search_server';

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

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

    Utility::getIndexTaskManager()->addItemsAll($this->getIndex());
  }

  /**
   * Tests various indexing scenarios for the Database search backend.
   *
   * Uses a single method to save time.
   */
  public function testFramework() {
    $this->insertExampleContent();
    $this->checkDefaultServer();
    $this->checkServerTables();
    $this->checkDefaultIndex();
    $this->updateIndex();
    $this->searchNoResults();
    $this->indexItems($this->indexId);
    $this->searchSuccess1();
    $this->checkFacets();
    $this->regressionTests();
    $this->editServer();
    $this->searchSuccess2();
    $this->clearIndex();

    $this->enableHtmlFilter();
    $this->indexItems($this->indexId);
    $this->disableHtmlFilter();
    $this->clearIndex();

    $this->searchNoResults();
    $this->regressionTests2();
    $this->checkModuleUninstall();
  }

  /**
   * Tests the server that was installed through default configuration files.
   */
  protected function checkDefaultServer() {
    $server = $this->getServer();
    $this->assertTrue((bool) $server, 'The server was successfully created.');
  }

  /**
   * Tests that all tables and all columns have been created.
   */
  protected function checkServerTables() {
    $db_info = \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)->get($this->indexId);
    $normalized_storage_table = $db_info['index_table'];
    $field_tables = $db_info['field_tables'];

    $this->assertTrue(\Drupal::database()->schema()->tableExists($normalized_storage_table), 'Normalized storage table exists');
    foreach ($field_tables as $field_table) {
      $this->assertTrue(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
      $this->assertTrue(\Drupal::database()->schema()->fieldExists($normalized_storage_table, $field_table['column']), new FormattableMarkup('Field column %column exists', array('%column' => $field_table['column'])));
    }
  }

  /**
   * Tests the index that was installed through default configuration files.
   */
  protected function checkDefaultIndex() {
    $index = $this->getIndex();
    $this->assertTrue((bool) $index, 'The index was successfully created.');

    $this->assertEqual($index->getTracker()->getTotalItemsCount(), 5, 'Correct item count.');
    $this->assertEqual($index->getTracker()->getIndexedItemsCount(), 0, 'All items still need to be indexed.');
  }

  /**
   * Checks whether changes to the index's fields are picked up by the server.
   */
  protected function updateIndex() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();

    // Remove a field from the index and check if the change is matched in the
    // server configuration.
    $field_id = $this->getFieldId('keywords');
    if (empty($index->getFields()[$field_id])) {
      throw new \Exception();
    }
    $index->getFields()[$field_id]->setIndexed(FALSE, TRUE);
    $index->save();

    $index_fields = array_keys($index->getOption('fields', array()));

    $db_info = \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)->get($this->indexId);
    $server_fields = array_keys($db_info['field_tables']);

    sort($index_fields);
    sort($server_fields);
    $this->assertEqual($index_fields, $server_fields);

    // Add the field back for the next assertions.
    $index->getFields(FALSE)[$field_id]->setIndexed(TRUE, TRUE);
    $index->save();
  }

  /**
   * Enables the "HTML Filter" processor for the index.
   */
  protected function enableHtmlFilter() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();

    $index->getFields(FALSE)[$this->getFieldId('body')]->setIndexed(TRUE, TRUE);

    $processors = $index->getOption('processors', array());
    $processors['html_filter'] = array(
      'processor_id' => 'html_filter',
      'weights' => array(),
      'settings' => array(),
    );
    $index->setOption('processors', $processors);
    $index->save();
  }

  /**
   * Disables the "HTML Filter" processor for the index.
   */
  protected function disableHtmlFilter() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();
    $processors = $index->getOption('processors');
    unset($processors['html_filter']);
    $index->setOption('processors', $processors);
    $index->getFields()[$this->getFieldId('body')]->setIndexed(FALSE, TRUE);
    $index->save();
  }

  /**
   * Builds a search query for testing purposes.
   *
   * Used as a helper method during testing.
   *
   * @param string|array|null $keys
   *   (optional) The search keys to set, if any.
   * @param array $conditions
   *   (optional) Conditions to set on the query, in the format "field,value".
   * @param array $fields
   *   (optional) Fulltext fields to search for the keys.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A search query on the test index.
   */
  protected function buildSearch($keys = NULL, array $conditions = array(), array $fields = array()) {
    $query = $this->getIndex()->query();
    if ($keys) {
      $query->keys($keys);
      if ($fields) {
        $query->setFulltextFields($fields);
      }
    }
    foreach ($conditions as $condition) {
      list($field, $value) = explode(',', $condition, 2);
      $query->addCondition($this->getFieldId($field), $value);
    }
    $query->range(0, 10);

    return $query;
  }

  /**
   * Tests that a search on the index doesn't have any results.
   */
  protected function searchNoResults() {
    $results = $this->buildSearch('test')->execute();
    $this->assertEqual($results->getResultCount(), 0, 'No search results returned without indexing.');
    $this->assertEqual(array_keys($results->getResultItems()), array(), 'No search results returned without indexing.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Tests whether some test searches have the correct results.
   */
  protected function searchSuccess1() {
    $results = $this->buildSearch('test')->range(1, 2)->sort($this->getFieldId('id'), QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Search for »test« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(2, 3)), 'Search for »test« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $ids = $this->getItemIds(array(2));
    $id = reset($ids);
    if ($this->assertEqual(key($results->getResultItems()), $id)) {
      $this->assertEqual($results->getResultItems()[$id]->getId(), $id);
      $this->assertEqual($results->getResultItems()[$id]->getDatasourceId(), 'entity:entity_test');
    }

    $results = $this->buildSearch('test foo')->sort($this->getFieldId('id'), QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 3, 'Search for »test foo« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4)), 'Search for »test foo« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo', array('type,item'))->sort($this->getFieldId('id'), QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 2, 'Search for »foo« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2)), 'Search for »foo« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Complex search 1 returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(4)), 'Complex search 1 returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Tests whether facets work correctly.
   */
  protected function checkFacets() {
    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR', array('facet:' . $this->getFieldId('category')));
    $conditions->addCondition($this->getFieldId('category'), 'article_category');
    $query->addConditionGroup($conditions);
    $facets['category'] = array(
      'field' => $this->getFieldId('category'),
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
      'operator' => 'or',
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 2, 'OR facets query returned correct number of results.');
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
      array('count' => 1, 'filter' => '!'),
    );
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, array($this, 'facetCompare'));
    $this->assertEqual($expected, $category_facets, 'Correct OR facets were returned');

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR', array('facet:' . $this->getFieldId('category')));
    $conditions->addCondition($this->getFieldId('category'), 'article_category');
    $query->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition($this->getFieldId('category'), NULL, '<>');
    $query->addConditionGroup($conditions);
    $facets['category'] = array(
      'field' => $this->getFieldId('category'),
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
      'operator' => 'or',
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 2, 'OR facets query returned correct number of results.');
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
    );
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, array($this, 'facetCompare'));
    $this->assertEqual($expected, $category_facets, 'Correct OR facets were returned');
  }

  /**
   * Edits the server to change the "Minimum word length" setting.
   */
  protected function editServer() {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['min_chars'] = 4;
    $server->setBackendConfig($backend_config);
    $success = (bool) $server->save();
    $this->assertTrue($success, 'The server was successfully edited.');

    $this->clearIndex();
    $this->indexItems($this->indexId);

    // Reset the internal cache so the new values will be available.
    \Drupal::entityManager()->getStorage('search_api_index')->resetCache(array($this->indexId));
  }

  /**
   * Tests the results of some test searches with minimum word length of 4.
   */
  protected function searchSuccess2() {
    $results = $this->buildSearch('test')->range(1, 2)->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Search for »test« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(4, 1)), 'Search for »test« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch(NULL, array('body,test foobar'))->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Search with multi-term fulltext filter returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'Search with multi-term fulltext filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('test foo')->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Search for »test foo« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(2, 4, 1, 3)), 'Search for »test foo« returned correct result.');
    $this->assertIgnored($results, array('foo'), 'Short key was ignored.');
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo', array('type,item'))->execute();
    $this->assertEqual($results->getResultCount(), 3, 'Search for »foo« returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 3)), 'Search for »foo« returned correct result.');
    $this->assertIgnored($results, array('foo'), 'Short key was ignored.');
    $this->assertWarnings($results, array((string) $this->t('No valid search keys were present in the query.')), '"No valid keys" warning was displayed.');

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Complex search 1 returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'Complex search 1 returned correct result.');
    $this->assertIgnored($results, array('baz', 'bar'), 'Correct keys were ignored.');
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Complex search 2 returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'Complex search 2 returned correct result.');
    $this->assertIgnored($results, array('baz', 'bar'), 'Correct keys were ignored.');
    $this->assertWarnings($results);

    $results = $this->buildSearch(NULL, array('keywords,orange'))->execute();
    $this->assertEqual($results->getResultCount(), 3, 'Filter query 1 on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 5)), 'Filter query 1 on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $conditions = array(
      'keywords,orange',
      'keywords,apple',
    );
    $results = $this->buildSearch(NULL, $conditions)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Filter query 2 on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(2)), 'Filter query 2 on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keywords_field = $this->getFieldId('keywords');
    $results = $this->buildSearch()->addCondition($keywords_field, 'orange', '<>')->execute();
    $this->assertEqual($results->getResultCount(), 2, 'Negated filter on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3, 4)), 'Negated filter on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition($keywords_field, NULL)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Query with NULL filter returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'Query with NULL filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition($keywords_field, NULL, '<>')->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Query with NOT NULL filter returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4, 5)), 'Query with NOT NULL filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Executes regression tests for issues that were already fixed.
   */
  protected function regressionTests() {
    // Regression tests for #2007872.
    $results = $this->buildSearch('test')->sort($this->getFieldId('id'), QueryInterface::SORT_ASC)->sort($this->getFieldId('type'), QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Sorting on field with NULLs returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 3, 4)), 'Sorting on field with NULLs returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition($this->getFieldId('id'), 3);
    $conditions->addCondition($this->getFieldId('type'), 'article');
    $query->addConditionGroup($conditions);
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 3, 'OR filter on field with NULLs returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3, 4, 5)), 'OR filter on field with NULLs returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #1863672.
    $keywords_field = $this->getFieldId('keywords');
    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition($keywords_field, 'orange');
    $conditions->addCondition($keywords_field, 'apple');
    $query->addConditionGroup($conditions);
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 4, 'OR filter on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4, 5)), 'OR filter on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition($keywords_field, 'orange');
    $conditions->addCondition($keywords_field, 'strawberry');
    $query->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition($keywords_field, 'apple');
    $conditions->addCondition($keywords_field, 'grape');
    $query->addConditionGroup($conditions);
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 3, 'Multiple OR filters on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(2, 4, 5)), 'Multiple OR filters on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions1 = $query->createConditionGroup('OR');
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition($keywords_field, 'orange');
    $conditions->addCondition($keywords_field, 'apple');
    $conditions1->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition($keywords_field, 'strawberry');
    $conditions->addCondition($keywords_field, 'grape');
    $conditions1->addConditionGroup($conditions);
    $query->addConditionGroup($conditions1);
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 3, 'Complex nested filters on multi-valued field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(2, 4, 5)), 'Complex nested filters on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2040543.
    $query = $this->buildSearch();
    $facets['category'] = array(
      'field' => $this->getFieldId('category'),
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
      array('count' => 1, 'filter' => '!'),
    );
    $type_facets = $results->getExtraData('search_api_facets')['category'];
    usort($type_facets, array($this, 'facetCompare'));
    $this->assertEqual($type_facets, $expected, 'Correct facets were returned');

    $query = $this->buildSearch();
    $facets['category']['missing'] = FALSE;
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
    );
    $type_facets = $results->getExtraData('search_api_facets')['category'];
    usort($type_facets, array($this, 'facetCompare'));
    $this->assertEqual($type_facets, $expected, 'Correct facets were returned');

    // Regression tests for #2111753.
    $keys = array(
      '#conjunction' => 'OR',
      'foo',
      'test',
    );
    $query = $this->buildSearch($keys, array(), array($this->getFieldId('name')));
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 3, 'OR keywords returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4)), 'OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch($keys, array(), array($this->getFieldId('name'), $this->getFieldId('body')));
    $query->range(0, 0);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 5, 'Multi-field OR keywords returned correct number of results.');
    $this->assertFalse($results->getResultItems(), 'Multi-field OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      'foo',
      'test',
      array(
        '#conjunction' => 'AND',
        'bar',
        'baz',
      ),
    );
    $query = $this->buildSearch($keys, array(), array($this->getFieldId('name')));
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Nested OR keywords returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4, 5)), 'Nested OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      array(
        '#conjunction' => 'AND',
        'foo',
        'test',
      ),
      array(
        '#conjunction' => 'AND',
        'bar',
        'baz',
      ),
    );
    $query = $this->buildSearch($keys, array(), array($this->getFieldId('name'), $this->getFieldId('body')));
    $query->sort($this->getFieldId('id'), QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 4, 'Nested multi-field OR keywords returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4, 5)), 'Nested multi-field OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2127001.
    $keys = array(
      '#conjunction' => 'AND',
      '#negation' => TRUE,
      'foo',
      'bar',
    );
    $results = $this->buildSearch($keys)->sort('search_api_id', QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 2, 'Negated AND fulltext search returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3, 4)), 'Negated AND fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      '#negation' => TRUE,
      'foo',
      'baz',
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Negated OR fulltext search returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'Negated OR fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'AND',
        '#negation' => TRUE,
        'foo',
        'bar',
      ),
    );
    $results = $this->buildSearch($keys)->sort('search_api_id', QueryInterface::SORT_ASC)->execute();
    $this->assertEqual($results->getResultCount(), 2, 'Nested NOT AND fulltext search returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3, 4)), 'Nested NOT AND fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2136409.
    $query = $this->buildSearch();
    $query->addCondition($this->getFieldId('category'), NULL);
    $query->sort('search_api_id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 1, 'NULL filter returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(3)), 'NULL filter returned correct result.');

    $query = $this->buildSearch();
    $query->addCondition($this->getFieldId('category'), NULL, '<>');
    $query->sort('search_api_id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 4, 'NOT NULL filter returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(1, 2, 4, 5)), 'NOT NULL filter returned correct result.');

    // Regression tests for #1658964.
    $query = $this->buildSearch();
    $facets['type'] = array(
      'field' => $this->getFieldId('type'),
      'limit' => 0,
      'min_count' => 0,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->addCondition($this->getFieldId('type'), 'article');
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article"'),
      array('count' => 0, 'filter' => '!'),
      array('count' => 0, 'filter' => '"item"'),
    );
    $facets = $results->getExtraData('search_api_facets', array())['type'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEqual($facets, $expected, 'Correct facets were returned');

    // Regression tests for #2469547.
    $query = $this->buildSearch();
    $facets = array();
    $facets['body'] = array(
      'field' => $this->getFieldId('body'),
      'limit' => 0,
      'min_count' => 1,
      'missing' => FALSE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->addCondition($this->getFieldId('id'), 5, '<>');
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 4, 'filter' => '"test"'),
      array('count' => 1, 'filter' => '"bar"'),
      array('count' => 1, 'filter' => '"foobar"'),
    );
    // We can't guarantee the order of returned facets, since "bar" and "foobar"
    // both occur once, so we have to do a more complex check.
    $facets = $results->getExtraData('search_api_facets', array())['body'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEqual($facets, $expected, 'Correct facets were returned for a fulltext field.');

    // Regression tests for #1403916.
    $query = $this->buildSearch('test foo');
    $facets = array();
    $facets['type'] = array(
      'field' => $this->getFieldId('type'),
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"item"'),
      array('count' => 1, 'filter' => '"article"'),
    );
    $facets = $results->getExtraData('search_api_facets', array())['type'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEqual($facets, $expected, 'Correct facets were returned');
  }

  /**
   * Compares two facet filters to determine their order.
   *
   * Used as a callback for usort() in regressionTests().
   *
   * Will first compare the counts, ranking facets with higher count first, and
   * then by filter value.
   *
   * @param array $a
   *   The first facet filter.
   * @param array $b
   *   The second facet filter.
   *
   * @return int
   *   -1 or 1 if the first filter should, respectively, come before or after
   *   the second; 0 if both facet filters are equal.
   */
  protected function facetCompare(array $a, array $b) {
    if ($a['count'] != $b['count']) {
      return $b['count'] - $a['count'];
    }
    return strcasecmp($a['filter'], $b['filter']);
  }

  /**
   * Clears the test index.
   */
  protected function clearIndex() {
    $this->getIndex()->clear();
  }

  /**
   * Executes regression tests which are unpractical to run in between.
   */
  protected function regressionTests2() {
    // Create a "prices" field on the test entity type.
    FieldStorageConfig::create(array(
      'field_name' => 'prices',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    FieldConfig::create(array(
      'field_name' => 'prices',
      'entity_type' => 'entity_test',
      'bundle' => 'item',
      'label' => 'Prices',
    ))->save();

    // Regression test for #1916474.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();
    $index->resetCaches();
    $fields = $index->getFields(FALSE);
    $price_field = $fields[$this->getFieldId('prices')];
    $price_field->setType('decimal')->setIndexed(TRUE, TRUE);
    $success = $index->save();
    $this->assertTrue($success, 'The index field settings were successfully changed.');

    // Reset the static cache so the new values will be available.
    \Drupal::entityManager()->getStorage('search_api_server')->resetCache(array($this->serverId));
    \Drupal::entityManager()->getStorage('search_api_index')->resetCache(array($this->serverId));

    \Drupal::entityManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 6,
        'prices' => array('3.5', '3.25', '3.75', '3.5'),
        'type' => 'item',
      ))->save();

    $this->indexItems($this->indexId);

    $query = $this->buildSearch(NULL, array('prices,3.25'));
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Filter on decimal field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(6)), 'Filter on decimal field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch(NULL, array('prices,3.5'));
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 1, 'Filter on decimal field returned correct number of results.');
    $this->assertEqual(array_keys($results->getResultItems()), $this->getItemIds(array(6)), 'Filter on decimal field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression test for #2284199.
    \Drupal::entityManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 7,
        'type' => 'item',
      ))->save();

    $count = $this->indexItems($this->indexId);
    $this->assertEqual($count, 1, 'Indexing an item with an empty value for a non string field worked.');

    // Regression test for #2471509.
    $index->getFields(FALSE)[$this->getFieldId('body')]->setIndexed(TRUE, TRUE);
    $index->save();
    $this->indexItems($this->indexId);

    \Drupal::entityManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 8,
        'name' => 'Article with long body',
        'type' => 'article',
        'body' => 'astringlongerthanfiftycharactersthatcantbestoredbythedbbackend',
      ))->save();
    $count = $this->indexItems($this->indexId);
    $this->assertEqual($count, 1, 'Indexing an item with a word longer than 50 characters worked.');

    $index->getFields(FALSE)[$this->getFieldId('body')]->setIndexed(FALSE, TRUE);
    $index->save();
  }

  /**
   * Tests whether removing the configuration again works as it should.
   */
  protected function checkModuleUninstall() {
    $db_info = \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)->get($this->indexId);
    $normalized_storage_table = $db_info['index_table'];
    $field_tables = $db_info['field_tables'];

    // See whether clearing the server works.
    // Regression test for #2156151.
    $server = $this->getServer();
    $index = $this->getIndex();
    $server->deleteAllIndexItems($index);
    $query = $this->buildSearch();
    $results = $query->execute();
    $this->assertEqual($results->getResultCount(), 0, 'Clearing the server worked correctly.');
    $this->assertTrue(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were left in place.');

    // Remove first the index and then the server.
    $index->setServer();
    $index->save();

    $db_info = \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)->get($this->indexId);
    $this->assertEqual($db_info, array(), 'The index was successfully removed from the server.');
    $this->assertFalse(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $this->assertFalse(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
    }

    // Re-add the index to see if the associated tables are also properly
    // removed when the server is deleted.

    $index->setServer($server);
    $index->save();
    $server->delete();

    $db_info = \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)->get($this->indexId);
    $this->assertEqual($db_info, array(), 'The index was successfully removed from the server.');
    $this->assertFalse(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $this->assertFalse(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
    }

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(array('search_api_db'), FALSE);
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_api_db'), 'The Database Search module was successfully uninstalled.');

    $tables = \Drupal::database()->schema()->findTables('search_api_db_%');
    $this->assertEqual($tables, [], 'All the tables of the the Database Search module have been removed.');
  }

  /**
   * Asserts ignored fields from a set of search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The results to check.
   * @param array $ignored
   *   (optional) The ignored keywords that should be present, if any.
   * @param string $message
   *   (optional) The message to be displayed with the assertion.
   */
  protected function assertIgnored(ResultSetInterface $results, array $ignored = array(), $message = 'No keys were ignored.') {
    $this->assertEqual($results->getIgnoredSearchKeys(), $ignored, $message);
  }

  /**
   * Asserts warnings from a set of search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The results to check.
   * @param array $warnings
   *   (optional) The ignored warnings that should be present, if any.
   * @param string $message
   *   (optional) The message to be displayed with the assertion.
   */
  protected function assertWarnings(ResultSetInterface $results, array $warnings = array(), $message = 'No warnings were displayed.') {
    $this->assertEqual($results->getWarnings(), $warnings, $message);
  }

  /**
   * Retrieves the search server used by this test.
   *
   * @return \Drupal\search_api\ServerInterface
   *   The search server.
   */
  protected function getServer() {
    return Server::load($this->serverId);
  }

  /**
   * Retrieves the search index used by this test.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index.
   */
  protected function getIndex() {
    return Index::load($this->indexId);
  }

}
