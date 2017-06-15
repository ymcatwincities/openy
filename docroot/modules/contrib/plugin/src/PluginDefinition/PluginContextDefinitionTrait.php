<?php

namespace Drupal\plugin\PluginDefinition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;

/**
 * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait PluginContextDefinitionTrait {

  /**
   * The context definitions.
   *
   * @var \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]
   */
  protected $contextDefinitions = [];

  /**
   * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface::setContextDefinitions().
   */
  public function setContextDefinitions(array $context_definitions) {
    PluginDefinitionValidator::validateContextDefinitions($context_definitions);

    $this->contextDefinitions = $context_definitions;

    return $this;
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface::getContextDefinitions().
   */
  public function getContextDefinitions() {
    return $this->contextDefinitions;
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface::setContextDefinition().
   */
  public function setContextDefinition($name, ContextDefinitionInterface $context_definition) {
    $this->contextDefinitions[$name] = $context_definition;

    return $this;
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface::getContextDefinition().
   */
  public function getContextDefinition($name) {
    if (!$this->hasContextDefinition($name)) {
      throw new \InvalidArgumentException(sprintf('Context %s does not exist.', $name));
    }

    return $this->contextDefinitions[$name];
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginContextDefinitionInterface::hasContextDefinition().
   */
  public function hasContextDefinition($name) {
    return isset($this->contextDefinitions[$name]);
  }

}
