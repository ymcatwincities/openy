<?php

namespace Drupal\plugin\PluginDefinition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

/**
 * Provides plugin definition validation.
 *
 * @ingroup Plugin
 */
class PluginDefinitionValidator {

  /**
   * Validates a plugin class.
   *
   * @param string $class
   *   A fully qualified class name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the class is invalid.
   */
  public static function validateClass($class) {
    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class %s does not exist.', $class));
    }
  }

  /**
   * Validates a plugin deriver class.
   *
   * @param string $class
   *   A fully qualified class name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the class is invalid.
   */
  public static function validateDeriverClass($class) {
    static::validateClass($class);

    if (!is_subclass_of($class, DeriverInterface::class)) {
      throw new \InvalidArgumentException(sprintf('Plugin deriver class %s does not implement required %s.', $class, DeriverInterface::class));
    }
  }

  /**
   * Validates plugin context definitions.
   *
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   The array of context definitions, keyed by context name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the class is invalid.
   */
  public static function validateContextDefinitions(array $context_definitions) {
    foreach ($context_definitions as $name => $context_definition) {
      if (!($context_definition instanceof ContextDefinitionInterface)) {
        $type = is_object($context_definition) ? get_class($context_definition) : gettype($context_definition);
        throw new \InvalidArgumentException(sprintf('$context_definition[%s] (%s) does not implement required %s.', $name, $type, ContextDefinitionInterface::class));
      }
    }
  }

}
