<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Implements \Drupal\plugin\PluginDefinition\PluginDeriverDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait PluginDeriverDefinitionTrait {

  /**
   * The deriver class.
   *
   * @var string
   *   The fully qualified name of a class that implements
   *   \Drupal\Component\Plugin\Derivative\DeriverInterface.
   */
  protected $deriverClass;

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginDeriverDefinitionInterface::setDeriverClass().
   */
  public function setDeriverClass($class) {
    PluginDefinitionValidator::validateClass($class);

    $this->deriverClass = $class;

    return $this;
  }

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginDeriverDefinitionInterface::getDeriverClass().
   */
  public function getDeriverClass() {
    return $this->deriverClass;
  }

}
