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
   *   The paragraph type ID.
   *
   * @return array
   *   The definitions.
   */
  public function getDefinitionsByParagraphType($type_id = '');

  /**
   * Gets the libraries filtered by skin name and paragraph keys.
   *
   * @param string $type_id
   *   The paragraph type ID.
   * @param string $theme_key
   *   Theme key to search.
   *
   * @return array
   *   The definitions
   */
  public function getLibraries($type_id, $skin_name);

}
