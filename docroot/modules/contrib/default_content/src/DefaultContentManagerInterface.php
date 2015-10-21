<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentManager.
 */

namespace Drupal\default_content;


/**
 * An interface defining a default content importer.
 */
interface DefaultContentManagerInterface {
  /**
   * Set the scanner.
   *
   * @param \Drupal\default_content\DefaultContentScanner $scanner
   *   The system scanner.
   */
  public function setScanner(DefaultContentScanner $scanner);

  /**
   * Imports default content for a given module.
   *
   * @param string $module
   *   The module to create the default content for.
   *
   * @return array[\Drupal\Core\Entity\EntityInterface]
   *   The created entities.
   */
  public function importContent($module);

  /**
   * Exports a single entity as importContent expects it.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param $entity_id
   *   The entity ID to export.
   *
   * @return string
   *   The rendered export as hal.
   */
  public function exportContent($entity_type_id, $entity_id);

  /**
   * Exports a single entity and all its referenced entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
   *   The entity ID to export.
   *
   * @return string[][]
   *   The serialized entities keyed by entity type and UUID.
   */
  public function exportContentWithReferences($entity_type_id, $entity_id);

}
