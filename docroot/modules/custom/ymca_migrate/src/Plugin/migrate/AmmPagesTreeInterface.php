<?php
/**
 * @file
 * Contract about getting tree of pages from source database.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Interface AmmPagesTreeInterface
 *
 * @package Drupal\ymca_migrate
 *
 * @todo add getCtType() and getskip_ids() if we'll need them.
 */
interface AmmPagesTreeInterface {

  /**
   * Update internal protected variable with array of IDs to be skipped from a tree.
   *
   * @param array $ids
   *   Array of IDs to be excluded.
   */
  public function setskip_ids(array $ids);

  /**
   * Update internal protected variable with array of IDs to be added to a tree.
   *
   * @param array $ids
   *   Array of IDs to be added.
   */
  public function setneeded_ids(array $ids);

  /**
   * Get tree of IDs by CT type with skipped ID removed.
   */
  public function getTree();

  /**
   * Static method for Singleton.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param array $needed_ids
   *   Array of IDs to be added.
   * @param \Drupal\Core\Database\Connection $database
   *   Database to be used for queries.
   * @param \Drupal\migrate\Row $row
   *   Migrate row that is processed.
   * @return \Drupal\ymca_migrate\Plugin\migrate\AmmPagesTreeInterface
   *   Returns self.
   */
  static public function init($skip_ids, $needed_ids,  Connection $database, Row $row);

}
