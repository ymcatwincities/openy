<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that includes a deriver.
 *
 * @ingroup Plugin
 */
interface PluginDeriverDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the deriver class.
   *
   * @param string $class
   *   The fully qualified name of a class that implements
   *   \Drupal\Component\Plugin\Derivative\DeriverInterface.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   */
  public function setDeriverClass($class);

  /**
   * Gets the deriver class.
   *
   * @return string|null
   *   The fully qualified name of a class that implements
   *   \Drupal\Component\Plugin\Derivative\DeriverInterface or null.
   */
  public function getDeriverClass();

}
