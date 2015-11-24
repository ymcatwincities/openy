<?php
/**
 * @file
 * Contract about getting tree of components from source database.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Interface AmmComponentsTreeInterface
 *
 * @package Drupal\ymca_migrate
 *
 * @todo add getCtType() and getSkipIds() if we'll need them.
 */
interface AmmComponentsTreeInterface {

  /**
   * Update internal protected variable with array of IDs to be skipped from a tree.
   *
   * @param array $ids
   *   Array of IDs to be added.
   */
  public function setSkipIds(array $ids);

  /**
   * Get tree of IDs by CT type with skipped ID removed.
   */
  public function getTree();

  /**
   * Static method for Singleton.
   *
   * @param array $skipIds
   *   Array of IDs to be skipped.
   * @param \Drupal\Core\Database\Connection $database
   *   Database to be used for queries.
   * @param \Drupal\migrate\Row $row
   *   Migrate row that is processed.
   * @return \Drupal\ymca_migrate\Plugin\migrate\AmmComponentsTreeInterface
   *   Returns self.
   */
  static public function init($skipIds, Connection $database, Row $row);

}
