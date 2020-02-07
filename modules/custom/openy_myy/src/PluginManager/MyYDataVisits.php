<?php

namespace Drupal\openy_myy\PluginManager;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class MyYDataVisits
 *
 * @package Drupal\openy_myy\PluginManager
 */
class MyYDataVisits extends DefaultPluginManager {

  /**
   * Constructs a MyYDataVisits object.
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
      'Plugin/MyYDataVisits',
      $namespaces,
      $module_handler,
      'Drupal\openy_myy\PluginManager\MyYDataVisitsInterface',
      'Drupal\openy_myy\Annotation\MyYDataVisits'
    );
    $this->alterInfo('openy_myy_data_visits');
    $this->setCacheBackend($cache_backend, 'openy_myy_data_visits_plugins');
  }

}
