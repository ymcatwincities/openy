<?php

namespace Drupal\default_content;

/**
 * An interface defining a default content exporter.
 */
interface ExporterInterface {

  /**
   * Exports a single entity as importContent expects it.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
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

  /**
   * Exports all of the content defined in a module's info file.
   *
   * @param string $module_name
   *   The name of the module.
   *
   * @return string[][]
   *   The serialized entities keyed by entity type and UUID.
   *
   * @throws \InvalidArgumentException
   *   If any UUID is not found.
   */
  public function exportModuleContent($module_name);

  /**
   * Writes an array of serialized entities to a given folder.
   *
   * @param string[][] $serialized_by_type
   *   An array of serialized entities keyed by entity type and UUID.
   * @param string $folder
   *   The folder to write files into.
   */
  public function writeDefaultContent(array $serialized_by_type, $folder);

}
