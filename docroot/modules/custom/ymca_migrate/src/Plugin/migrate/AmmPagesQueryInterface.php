<?php
/**
 * @file
 * Contract about getting tree of pages from source database.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;

/**
 * Interface AmmPagesQueryInterface
 *
 * @package Drupal\ymca_migrate
 *
 * @todo add getCtType() and getskip_ids() if we'll need them.
 */
interface AmmPagesQueryInterface {

  /**
   * Update internal protected variable with array of IDs to be skipped from a tree.
   *
   * @param array $ids
   *   Array of IDs to be excluded.
   */
  public function setSkipIds(array $ids);

  /**
   * Update internal protected variable with array of IDs shouldn't be skipped from a tree.
   *
   * @param array $ids
   *   Array of IDs to be added.
   */
  public function setNeededIds(array $ids);

  /**
   * Get tree of IDs by CT type with skipped ID removed.
   */
  public function getIdsByParent($id);

  /**
   * Static method for Singleton.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param array $needed_ids
   *   Array of IDs to be added.
   * @param \Drupal\Core\Database\Connection $database
   *   Database to be used for queries.
   * @return \Drupal\ymca_migrate\Plugin\migrate\AmmPagesQueryInterface
   *   Returns self.
   */
  static public function init($skip_ids, $needed_ids, Connection $database);

}
