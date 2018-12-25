<?php

namespace Drupal\Tests\search_api_solr\Kernel\Processor;

/**
 * Tests the "Content access" processor.
 *
 * @group search_api_solr
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\ContentAccess
 */
class ContentAccessTest extends \Drupal\Tests\search_api\Kernel\Processor\ContentAccessTest  {

  use SolrBackendTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api_solr',
    'search_api_solr_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp();
    $this->enableSolrServer('search_api_solr_test', '/config/install/search_api.server.solr_search_server.yml');
  }

}
