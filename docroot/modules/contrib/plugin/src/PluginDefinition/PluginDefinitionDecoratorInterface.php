<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition decorator.
 *
 * As this interface cannot predict which plugin definitions its implementations
 * can decorate, it is somewhat generic. When using this interface, developers
 * are responsible for only using it with definitions that implementations
 * support.
 *
 * @ingroup Plugin
 */
interface PluginDefinitionDecoratorInterface extends PluginDefinitionInterface {

  /**
   * Creates a new plugin definition that decorates another definition.
   *
   * @param mixed $decorated_plugin_definition
   *   The plugin definition to decorate. The supported types depend on the
   *   implementations of this method.
   *
   * @return static
   */
  public static function createFromDecoratedDefinition($decorated_plugin_definition);

}
