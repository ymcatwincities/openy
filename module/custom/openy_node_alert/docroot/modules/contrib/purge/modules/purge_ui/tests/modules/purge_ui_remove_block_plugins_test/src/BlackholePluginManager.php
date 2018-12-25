<?php

namespace Drupal\purge_ui_remove_block_plugins_test;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * PluginManager that will never deliver any plugins (for testing purposes).
 */
class BlackholePluginManager extends DefaultPluginManager {

  /**
   * Constructs the BlackholePluginManager object.
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
    parent::__construct(
      'Plugin/Purge/Blackhole',
      $namespaces,
      $module_handler,
      'Drupal\purge\Plugin\Purge\Queue\QueueInterface',
      'Drupal\purge\Annotation\PurgeQueue');
    $this->setCacheBackend($cache_backend, 'purge_here_are_no_plugins');
  }

}
