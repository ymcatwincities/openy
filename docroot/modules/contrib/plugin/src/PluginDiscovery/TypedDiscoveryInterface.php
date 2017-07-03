<?php

namespace Drupal\plugin\PluginDiscovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * An interface defining the minimum requirements of building a plugin
 * discovery component.
 *
 * @ingroup plugin_api
 */
interface TypedDiscoveryInterface extends DiscoveryInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\plugin\PluginDefinition\PluginDefinitionInterface|NULL
   *   A plugin definition, or NULL if the plugin ID is invalid and
   *   $exception_on_invalid is FALSE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\plugin\PluginDefinition\PluginDefinitionInterface[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitions();

}
