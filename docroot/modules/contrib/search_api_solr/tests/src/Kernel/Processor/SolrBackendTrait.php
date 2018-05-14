<?php

namespace Drupal\Tests\search_api_solr\Kernel\Processor;

use Drupal\search_api\Entity\Server;
use Symfony\Component\Yaml\Yaml;

/**
 * Helper to exchange the DB backend for a Solr backend in processor tests.
 */
trait SolrBackendTrait {

  /**
   * Swap the DB backend for a Solr backend.
   *
   * This function has to be called from the test setUp() function.
   *
   * @param string $module
   *   The module that provides the server config.
   * @param $config
   *   The server config
   */
  protected function enableSolrServer($module, $config) {
    $this->server = Server::create(
      Yaml::parse(file_get_contents(
        drupal_get_path('module', $module) . $config
      ))
    );
    $this->server->save();

    $this->index->setServer($this->server);
    $this->index->save();

    $index_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('search_api_index');
    $index_storage->resetCache(array($this->index->id()));
    $this->index = $index_storage->load($this->index->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function indexItems() {
    $index_status = parent::indexItems();
    sleep(2);
    return $index_status;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->index->clear();
    sleep(2);
    parent::tearDown();
  }

}
