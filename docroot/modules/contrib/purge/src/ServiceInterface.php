<?php

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Describes a generic service for all DIC-registered service classes by Purge.
 */
interface ServiceInterface extends ServiceProviderInterface, ServiceModifierInterface {

  /**
   * Retrieve a list of all available plugins providing the service.
   *
   * @return array[]
   *   Associative array with plugin definitions and the plugin_id in each key.
   *
   * @see \Drupal\Component\Plugin\PluginManagerInterface::getDefinitions()
   */
  public function getPlugins();

  /**
   * Retrieve the configured plugin_ids that the service will use.
   *
   * @return string[]
   *   Array with the plugin_ids of the enabled plugins.
   */
  public function getPluginsEnabled();

  /**
   * Find out whether the given plugin_id is enabled.
   *
   * @param string $plugin_id
   *   The plugin_id of the plugin you want to check for.
   *
   * @return true|false
   */
  public function isPluginEnabled($plugin_id);

  /**
   * Reload the service and reinstantiate all enabled plugins.
   *
   * @warning
   *   Reloading a service implies that all cached data will be reset and that
   *   plugins get reinstantiated during the current request, which should
   *   normally not be used. This method is specifically used in tests.
   */
  public function reload();

}
