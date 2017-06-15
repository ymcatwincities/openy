<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Implements the plugin merger parts of \Drupal\plugin\PluginDefinition\PluginDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait MergeablePluginDefinitionTrait {

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginDefinitionInterface::mergeDefaultDefinition().
   */
  public function mergeDefaultDefinition(PluginDefinitionInterface $other_definition) {
    $this->validateMergeDefinition($other_definition);
    $this->doMergeDefaultDefinition($other_definition);

    return $this;
  }

  /**
   * Merges another definition into this one, using the other for defaults.
   *
   * @param static $other_definition
   *   The other definition to merge into $this. It will not override $this, but
   *   be used to extract default values from instead.
   */
  protected function doMergeDefaultDefinition(PluginDefinitionInterface $other_definition) {
    // Child classes can override this to perform an actual merge.
  }

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginDefinitionInterface::mergeOverrideDefinition().
   */
  public function mergeOverrideDefinition(PluginDefinitionInterface $other_definition) {
    $this->validateMergeDefinition($other_definition);
    $this->doMergeOverrideDefinition($other_definition);

    return $this;
  }

  /**
   * Merges another definition into this one, using the other for overrides.
   *
   * @param static $other_definition
   *   The other definition to merge into $this. It will override any values
   *   already set in $this.
   */
  protected function doMergeOverrideDefinition(PluginDefinitionInterface $other_definition) {
    // Child classes can override this to perform an actual merge.
  }

  /**
   * Validates whether another definition is compatible with this one.
   *
   * @throws \InvalidArgumentException
   */
  protected function validateMergeDefinition(PluginDefinitionInterface $other_definition) {
    if (!$this->isDefinitionCompatible($other_definition)) {
      throw new \InvalidArgumentException(sprintf('$other_definition must be an instance of %s, but %s was given.', get_class($this), get_class($other_definition)));
    }
  }

  /**
   * Returns whether another definition is compatible with this one.
   *
   * @return bool
   *   Whether or not the definition is compatible with $this.
   */
  abstract protected function isDefinitionCompatible(PluginDefinitionInterface $other_definition);

}
