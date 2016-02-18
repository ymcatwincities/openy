<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Interface AmmComponentsTreeInterface.
 *
 * @package Drupal\ymca_migrate
 *
 * @todo add getCtType() and getskip_ids() if we'll need them.
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
   * Get tree of IDs by Component Type with skipped ID removed.
   */
  public function getTree();

  /**
   * Static method for Singleton.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $database
   *   Database to be used for queries.
   * @param \Drupal\migrate\Row $row
   *   Migrate row that is processed.
   *
   * @return \Drupal\ymca_migrate\Plugin\migrate\AmmComponentsTreeInterface
   *   Returns self.
   */
  static public function init($skip_ids, SqlBase &$database, Row $row);

}
