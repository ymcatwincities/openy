<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that includes a description.
 *
 * @ingroup Plugin
 */
interface PluginDescriptionDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the human-readable plugin description.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $description
   *   The description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the human-readable plugin description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   The description or NULL if there is none.
   */
  public function getDescription();

}
