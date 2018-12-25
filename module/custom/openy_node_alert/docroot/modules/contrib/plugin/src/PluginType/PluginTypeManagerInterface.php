<?php

namespace Drupal\plugin\PluginType;

/**
 * Defines a plugin type manager.
 */
interface PluginTypeManagerInterface {

  /**
   * Checks whether a plugin type is known.
   *
   * @param string $id
   *   The plugin type's ID.
   *
   * @return bool
   */
  public function hasPluginType($id);

  /**
   * Gets a known plugin type.
   *
   * @param string $id
   *   The plugin type's ID.
   *
   * @return \Drupal\plugin\PluginType\PluginTypeInterface
   *
   * @throws \InvalidArgumentException
   *   Thrown if the plugin type is unknown.
   */
  public function getPluginType($id);

  /**
   * Gets the known plugin types.
   *
   * @return \Drupal\plugin\PluginType\PluginTypeInterface[]
   */
  public function getPluginTypes();

}
