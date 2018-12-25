<?php

namespace Drupal\plugin\PluginDefinition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;

/**
 * Defines a plugin definition that includes contexts.
 *
 * @ingroup Plugin
 */
interface PluginContextDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the context definitions.
   *
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   The array of context definitions, keyed by context name.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the definitions are invalid.
   */
  public function setContextDefinitions(array $context_definitions);

  /**
   * Gets the context definitions.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]
   *   The array of context definitions, keyed by context name.
   */
  public function getContextDefinitions();

  /**
   * Sets a specific context definition.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface $context_definition
   *   The context definition to set.
   *
   * @return $this
   */
  public function setContextDefinition($name, ContextDefinitionInterface $context_definition);

  /**
   * Gets a specific context definition.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   *
   * @throws \InvalidArgumentException
   *   If the requested context does not exist.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface
   *
   * @see self::hasContextDefinition()
   */
  public function getContextDefinition($name);

  /**
   * Checks if a specific context definition exists.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   *
   * @return bool
   *   Whether the context definition exists.
   */
  public function hasContextDefinition($name);

}
