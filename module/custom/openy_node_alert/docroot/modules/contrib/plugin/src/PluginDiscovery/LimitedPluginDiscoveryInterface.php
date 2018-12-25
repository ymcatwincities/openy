<?php

namespace Drupal\plugin\PluginDiscovery;

/**
 * Defines a limited plugin discovery.
 */
interface LimitedPluginDiscoveryInterface {

  /**
   * Limits the plugins to discover.
   *
   * If this filter is set, any action for any plugin ID that is not part of the
   * filter must result in a
   * \Drupal\Component\Plugin\Exception\PluginNotFoundException being thrown.
   *
   * @param string[] $plugin_ids
   *   An array of plugin IDs or TRUE to allow all.
   *
   * @return $this
   */
  public function setDiscoveryLimit(array $plugin_ids);

  /**
   * Resets the discovery limit.
   *
   * @return $this
   */
  public function resetDiscoveryLimit();

}
