<?php

namespace Drupal\plugin\DefaultPluginResolver;

use Drupal\plugin\PluginType\PluginTypeInterface;

/**
 * Defines a default plugin resolver.
 */
interface DefaultPluginResolverInterface {

  /**
   * Creates a default plugin instance of a given plugin type.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|null
   *   A plugin instance or NULL of no default could be created.
   */
  public function createDefaultPluginInstance(PluginTypeInterface $plugin_type);

}
