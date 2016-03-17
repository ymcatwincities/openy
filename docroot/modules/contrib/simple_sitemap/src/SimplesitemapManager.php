<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\SimplesitemapManager.
 */

namespace Drupal\simple_sitemap;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Simplesitemap plugin manager.
 */
class SimplesitemapManager extends DefaultPluginManager {

  /**
   * Constructs an SimplesitemapManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/LinkGenerator', $namespaces, $module_handler, 'Drupal\simple_sitemap\LinkGeneratorInterface', 'Drupal\simple_sitemap\Annotation\LinkGenerator');

    $this->alterInfo('simple_sitemap_link_generators_info');
    $this->setCacheBackend($cache_backend, 'simple_sitemap_link_generators');
  }
}
