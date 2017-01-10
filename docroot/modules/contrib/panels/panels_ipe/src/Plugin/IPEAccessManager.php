<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\IPEAccessManager.
 */

namespace Drupal\panels_ipe\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Provides the IPE Access plugin manager.
 */
class IPEAccessManager extends DefaultPluginManager implements IPEAccessManagerInterface {

  /**
   * Constructor for IPEAccessManager objects.
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
    parent::__construct('Plugin/IPEAccess', $namespaces, $module_handler, 'Drupal\panels_ipe\Plugin\IPEAccessInterface', 'Drupal\panels_ipe\Annotation\IPEAccess');

    $this->alterInfo('panels_ipe_ipe_access_info');
    $this->setCacheBackend($cache_backend, 'panels_ipe_ipe_access_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PanelsDisplayVariant $display) {
    $applies = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\panels_ipe\Plugin\IPEAccessInterface $plugin */
      $plugin = $this->createInstance($plugin_id);
      if ($plugin->applies($display)) {
        $applies[$plugin_id] = $plugin;
      }
    }
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function access(PanelsDisplayVariant $display) {
    foreach ($this->applies($display) as $plugin_id => $plugin) {
      if (!$plugin->access($display)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
