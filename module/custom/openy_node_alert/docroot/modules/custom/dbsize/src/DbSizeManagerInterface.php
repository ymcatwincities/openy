<?php

namespace Drupal\dbsize;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface for DbSizeManager.
 */
interface DbSizeManagerInterface {

  /**
   * Get size of the table or tables.
   *
   * @param array $tables
   *   Table name.
   *
   * @return mixed
   *   Table size in bytes.
   */
  public function getTablesSize(array $tables);

  /**
   * Get size of the content entity type.
   *
   * @param string $entity_type_id
   *   Entity type.
   *
   * @return mixed
   *   Entity size in bytes.
   */
  public function getEntitySize($entity_type_id);

  /**
   * Get a list of tables for entity type.
   *
   * @param string $entity_type_id
   *   Entity type.
   *
   * @return array
   *   The list of tables.
   */
  public function getEntityTables($entity_type_id);

  /**
   * Covert entity tables to specific engine.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param string $engine
   *   Engine name. Example: MyISAM.
   */
  public function convertEntityTablesEngine($entity_type_id, $engine);

  /**
   * Repair tables for specific entity type.
   *
   * @param string $entity_type_id
   *   Entity type.
   */
  public function repairEntityTables($entity_type_id);

}
