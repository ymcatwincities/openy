<?php

namespace Drupal\openy_myy\PluginManager;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;


class MyYDataProfile extends DefaultPluginManager {

  /**
   * Constructs a MyYDataProfile object.
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
      'Plugin/MyYDataProfile',
      $namespaces,
      $module_handler,
      'Drupal\openy_myy\PluginManager\MyYDataProfileInterface',
      'Drupal\openy_myy\Annotation\MyYDataProfile'
    );
    $this->alterInfo('openy_myy_authenticator');
    $this->setCacheBackend($cache_backend, 'openy_myy_data_profile_plugins');
  }

}