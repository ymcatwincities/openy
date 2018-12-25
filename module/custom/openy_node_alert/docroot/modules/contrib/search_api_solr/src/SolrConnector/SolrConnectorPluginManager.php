<?php

namespace Drupal\search_api_solr\SolrConnector;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for Solr connector plugins.
 *
 * @see \Drupal\search_api_solr\Annotation\SolrConnector
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorInterface
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase
 *
 * @ingroup plugin_api
 */
class SolrConnectorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a SolrConnectorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('search_api_solr_connector_info');
    $this->setCacheBackend($cache_backend, 'search_api_solr_connector_plugins');

    parent::__construct('Plugin/SolrConnector', $namespaces, $module_handler, 'Drupal\search_api_solr\SolrConnectorInterface', 'Drupal\search_api_solr\Annotation\SolrConnector');
  }

}
