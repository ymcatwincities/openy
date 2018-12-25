<?php

namespace Drupal\Tests\search_api_solr\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\search_api\Functional\SearchApiBrowserTestBase;

/**
 * Tests the Views integration of the Search API.
 *
 * @group search_api_solr
 */
class ViewsTest extends \Drupal\Tests\search_api\Functional\ViewsTest {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('search_api_solr_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Skip parent::setUp().
    SearchApiBrowserTestBase::setUp();

    // Add a second language.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Swap database backend for Solr backend.
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('search_api.index.database_search_index')
      ->delete();
    $config_factory->rename('search_api.index.solr_search_index', 'search_api.index.database_search_index');
    $config_factory->getEditable('search_api.index.database_search_index')
      ->set('id', 'database_search_index')
      ->save();

    // Now do the same as parent::setUp().
    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->insertExampleContent();
    $this->indexItems($this->indexId);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $index = Index::load($this->indexId);
    $index->clear();
    sleep(2);
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public function testView() {
    // @see https://www.drupal.org/node/2773019
    $query = ['language' => ['***LANGUAGE_language_interface***']];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with interface language as filter');

    parent::testView();
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @param string $index_id
   *   The ID of the index on which items should be indexed.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems($index_id) {
    $index_status = parent::indexItems($index_id);
    sleep(2);
    return $index_status;
  }

}
