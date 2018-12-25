<?php

namespace Drupal\ymca_google;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class GCalUpdaterManager.
 *
 * @package Drupal\ymca_google
 */
class GCalUpdaterManager extends DefaultPluginManager {

  /**
   * Plugin directory.
   */
  const PLUGIN_DIR = 'Plugin/GCalUpdater';

  /**
   * Plugin interface.
   */
  const PLUGIN_INTERFACE = 'Drupal\ymca_google\GCalUpdaterInterface';

  /**
   * Plugin annotation class.
   */
  const PLUGIN_ANNOTATION = 'Drupal\Component\Annotation\Plugin';

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable.
   * @param CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(self::PLUGIN_DIR, $namespaces, $module_handler, self::PLUGIN_INTERFACE, self::PLUGIN_ANNOTATION);

    $this->alterInfo('gcal_updater_info');
    $this->setCacheBackend($cache_backend, 'gcal_updater_info');
  }

}
