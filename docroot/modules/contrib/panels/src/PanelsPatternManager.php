<?php

namespace Drupal\panels;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages PanelsPattern plugins.
 */
class PanelsPatternManager extends DefaultPluginManager {

  /**
   * PanelsPatternManager constructor.
   *
   * @param \Traversable $namespaces
   *   The namespaces to search for plugins.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('panels_pattern_info');
    $this->setCacheBackend($cache_backend, 'panels_pattern_plugins');

    parent::__construct('Plugin/PanelsPattern', $namespaces, $module_handler, 'Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface', '\Drupal\panels\Annotation\PanelsPattern');
  }

}
