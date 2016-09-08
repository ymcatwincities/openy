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
   *   Table name
   *
   * @return mixed
   *   Table size in bytes.
   */
  public function getTablesSize(array $tables);

  /**
   * Get size of the content entity type.
   *
   * @param ContentEntityTypeInterface $entityType
   *   Entity type.
   *
   * @return mixed
   *   Entity size in bytes.
   */
  public function getEntitySize(ContentEntityTypeInterface $entityType);

}
