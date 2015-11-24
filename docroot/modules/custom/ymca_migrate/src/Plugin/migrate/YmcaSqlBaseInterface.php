<?php
/**
 * @file
 * Implementation contract for Ymca$CTTree.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

interface YmcaSqlBaseInterface {

  /**
   * YmcaSqlBaseInterface constructor.
   *
   * @param array $skipIds
   *   IDs to be skipped.
   * @param \Drupal\Core\Database\Connection $database
   *   Database to be used for SQL queries.
   * @param \Drupal\migrate\Row $row
   *   Migrate row is currently processed.
   */
  public function __construct($skipIds, Connection $database, Row $row);
}