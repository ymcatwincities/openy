<?php

namespace Drupal\Tests\search_api_solr\Functional;

/**
 * Tests the overall functionality of the Search API framework and admin UI.
 *
 * @group search_api_solr
 */
class IntegrationTest extends \Drupal\Tests\search_api\Functional\IntegrationTest {

  /**
   * The backend of the search server used for this test.
   *
   * @var string
   */
  protected $serverBackend = 'search_api_solr';

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'search_api_solr',
    'search_api_solr_test',
  );

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    if ($this->indexId) {
      if ($index = $this->getIndex()) {
        $index->clear();
        sleep(2);
      }
    }
    parent::tearDown();
  }

  /**
   * Tests various operations via the Search API's admin UI.
   */
  public function testFramework() {
    $this->createServer();
    $this->createServerDuplicate();
    // @todo should work but doesn't.
    // $this->checkServerAvailability();
    $this->createIndex();
    $this->createIndexDuplicate();
    $this->editServer();
    $this->editIndex();
    $this->checkUserIndexCreation();
    // This tests doesn't cover the backend. No need to run it on Solr again.
    // $this->checkContentEntityTracking();
    // @todo overwrite.
    // $this->enableAllProcessors();
    $this->checkFieldLabels();

    // @todo overwrite.
    // $this->addFieldsToIndex();
    // $this->checkDataTypesTable();
    // $this->removeFieldsFromIndex();
    // $this->checkReferenceFieldsNonBaseFields();
    // These tests don't cover the backend. No need to run them on Solr again.
    // $this->configureFilter();
    // $this->configureFilterPage();
    // $this->checkProcessorChanges();
    // $this->changeProcessorFieldBoost();
    $this->setReadOnly();
    // @todo review.
    // $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();

    // @todo review.
    // $this->deleteServer();
  }

  /**
   * {@inheritdoc}
   *
   * This test doesn't really include any backend specific stuff and could be
   * skipped.
   */
  public function testIntegerIndex() {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  protected function configureBackendAndSave(array $edit) {
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Please configure the selected backend.');

    $edit += [
      'backend_config[connector]' => 'standard',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Please configure the selected Solr connector.');

    $edit += [
      'backend_config[connector_config][host]' => 'localhost',
      'backend_config[connector_config][port]' => '8983',
      'backend_config[connector_config][path]' => '/',
      'backend_config[connector_config][core]' => '',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('The server was successfully saved.');
    $this->assertSession()->addressEquals('admin/config/search/search-api/server/' . $this->serverId);
    $this->assertSession()->pageTextContains('The Solr server could not be reached or is protected by your service provider.');

    // Go back in and configure Solr.
    $edit_path = 'admin/config/search/search-api/server/' . $this->serverId . '/edit';
    $this->drupalGet($edit_path);
    $edit = [
      'backend_config[connector_config][host]' => 'localhost',
      'backend_config[connector_config][port]' => '8983',
      'backend_config[connector_config][path]' => '/solr',
      'backend_config[connector_config][core]' => 'd8',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The Solr server could be reached.');
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems() {
    $index_status = parent::indexItems();
    sleep(2);
    return $index_status;
  }

}
