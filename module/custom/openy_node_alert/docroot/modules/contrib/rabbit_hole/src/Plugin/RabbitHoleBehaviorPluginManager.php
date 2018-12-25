<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Rabbit hole behavior plugin plugin manager.
 */
class RabbitHoleBehaviorPluginManager extends DefaultPluginManager {

  /**
   * Constructor for RabbitHoleBehaviorPluginManager objects.
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
    parent::__construct('Plugin/RabbitHoleBehaviorPlugin', $namespaces, $module_handler, 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface', 'Drupal\rabbit_hole\Annotation\RabbitHoleBehaviorPlugin');

    $this->alterInfo('rabbit_hole_rabbit_hole_behavior_plugin_info');
    $this->setCacheBackend($cache_backend, 'rabbit_hole_rabbit_hole_behavior_plugin_plugins');
  }

}
