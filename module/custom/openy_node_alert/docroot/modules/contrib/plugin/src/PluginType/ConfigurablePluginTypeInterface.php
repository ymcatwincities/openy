<?php

namespace Drupal\plugin\PluginType;

/**
 * Defines a plugin type of which the plugins are configurable.
 */
interface ConfigurablePluginTypeInterface extends PluginTypeInterface {

  /**
   * Gets the ID of the configuration schema for a plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the plugin for whose configuration to get the schema ID.
   *
   * @return string
   */
  public function getPluginConfigurationSchemaId($plugin_id);

}
