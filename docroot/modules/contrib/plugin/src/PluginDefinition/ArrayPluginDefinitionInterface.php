<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition.
 *
 * For backwards compatibility with array-based plugin definitions, this
 * interface implements \ArrayAccess. The required array keys and their
 * corresponding setters and getters are:
 * - id: static::setId() and static::getId()
 * - class: static::setClass() and static::getClass()
 * - label: static::setLabel() and static::getLabel()
 * - deriver: static::setDeriverClass() and static::getDeriverClass()
 * - context: static::setContextDefinitions() and static::getContextDefinitions()
 *
 * @ingroup Plugin
 *
 * @deprecated Deprecated as of 8.0.0. Do not rely on array plugin
 *   definitions.
 */
interface ArrayPluginDefinitionInterface extends PluginDefinitionInterface, \ArrayAccess, \IteratorAggregate, \Countable {

  /**
   * Gets the array definition.
   *
   * @return mixed[]
   *   The array definition.
   */
  public function getArrayDefinition();

  /**
   * Merges another array definition into this one, using the other for defaults.
   *
   * @param mixed[] $other_definition
   *   The other array definition.
   *
   * @return $this
   */
  public function mergeDefaultArrayDefinition(array $other_definition);

  /**
   * Merges another array definition into this one, using the other for overrides.
   *
   * @param mixed[] $other_definition
   *   The other array definition.
   *
   * @return $this
   */
  public function mergeOverrideArrayDefinition(array $other_definition);

}
