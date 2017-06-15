<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that defines a parent plugin.
 *
 * @ingroup Plugin
 */
interface PluginHierarchyDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the ID of the parent plugin.
   *
   * @param string $id
   *   The ID.
   *
   * @return $this
   */
  public function setParentId($id);

  /**
   * Gets the ID of the parent plugin.
   *
   * @return string|null
   *   The ID or NULL if there is no parent.
   */
  public function getParentId();

}
