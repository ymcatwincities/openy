<?php

namespace Drupal\plugin;

/**
 * Defines a class that can get operations providers for plugins.
 */
interface PluginOperationsProviderProviderInterface {

  /**
   * Gets the plugin's operations provider.
   *
   * @param string $plugin_id
   *
   * @return \Drupal\plugin\PluginOperationsProviderInterface|null
   *   The operations provider or NULL if none is available.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOperationsProvider($plugin_id);

}
