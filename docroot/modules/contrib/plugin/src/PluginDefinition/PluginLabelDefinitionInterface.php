<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that includes a label.
 *
 * @ingroup Plugin
 */
interface PluginLabelDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the human-readable plugin label.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableString|string $label
   *   The label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the human-readable plugin label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableString|string|null
   *   The label or NULL if there is none.
   */
  public function getLabel();

}
