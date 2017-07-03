<?php

namespace Drupal\plugin\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plugin\Annotation\PluginSelector;

/**
 * Manages discovery and instantiation of plugin selector plugins.
 *
 * @see \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
 */
class PluginSelectorManager extends DefaultPluginManager implements PluginSelectorManagerInterface, FallbackPluginManagerInterface {

  /**
   * Constructs a new instance.
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
    parent::__construct('Plugin/Plugin/PluginSelector', $namespaces, $module_handler, PluginSelectorInterface::class, PluginSelector::class);
    $this->alterInfo('plugin_selector');
    $this->setCacheBackend($cache_backend, 'plugin_selector');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'plugin_select_list';
  }

}
