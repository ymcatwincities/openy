<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Interface AmmBlogsQueryInterface.
 *
 * @package Drupal\ymca_migrate
 *
 * @todo add  getskip_ids() if we'll need it.
 */
interface AmmBlogsQueryInterface {

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
   * Get list of IDs for migration.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Returns prepared query for migration.
   */
  public function getQuery();

  /**
   * Static method for Singleton.
   *
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration to be used for logging.
   *
   * @return AmmBlogsQueryInterface
   *   Return self.
   */
  static public function init(SqlBase &$database, MigrationInterface &$migration);

}
