<?php

namespace Drupal\panelizer\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Panelizer entity plugin manager.
 */
class PanelizerEntityManager extends DefaultPluginManager implements PanelizerEntityManagerInterface {

  /**
   * Constructor for PanelizerEntityManager objects.
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
    parent::__construct('Plugin/PanelizerEntity', $namespaces, $module_handler, 'Drupal\panelizer\Plugin\PanelizerEntityInterface', 'Drupal\panelizer\Annotation\PanelizerEntity');

    $this->alterInfo('panelizer_entity_info');
    $this->setCacheBackend($cache_backend, 'panelizer_entity_plugins');
  }

}
