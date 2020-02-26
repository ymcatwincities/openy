<?php

namespace Drupal\paragraph_skins;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for paragraph_skin plugin managers.
 */
interface SkinManagerInterface extends PluginManagerInterface {

  /**
   * Gets the definitions filtered by paragraph type.
   *
   * @param string $type_id
   *   The entity type ID.
   *
   * @return array
   *   The definitions.
   */
  public function getDefinitionsByParagraphType($type_id = NULL);

}
