<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that includes an operations provider.
 *
 * @ingroup Plugin
 */
interface PluginOperationsProviderDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the operations provider class.
   *
   * @param string $class
   *   The fully qualified name of a class that implements
   *   \Drupal\plugin\PluginOperationsProviderInterface.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   */
  public function setOperationsProviderClass($class);

  /**
   * Gets the operations provider class.
   *
   * @return string|null
   *   The fully qualified name of a class that implements
   *   \Drupal\plugin\PluginOperationsProviderInterface or null.
   */
  public function getOperationsProviderClass();

}
