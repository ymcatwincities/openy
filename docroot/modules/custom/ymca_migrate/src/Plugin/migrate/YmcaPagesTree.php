<?php
/**
 * @file
 * Class for getting children tree by top menu item.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

class YmcaPagesTree extends AmmPagesTree {

  /**
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesTree
   */
  static private $instance;

  /**
   * YmcaBlogComponentsTree constructor.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param array $needed_ids
   *   Array of IDs to be added to tree creation.
   * @param \Drupal\Core\Database\Connection $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Row $row
   *   Row that is processed within a Tree
   *
   * @return \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesTree $this
   *   Returns itself.
   */
  protected function __construct($skip_ids, $needed_ids, Connection $database, Row $row) {
    $this->database = $database;
    $this->row = $row;
    $this->tree = [];
    parent::__construct('page', $skip_ids, $needed_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    // TODO: Implement getTree() method.
  }

  /**
   * {@inheritdoc}
   */
  static public function init(
    $skip_ids,
    $needed_ids,
    Connection $database,
    Row $row
  ) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skip_ids, $needed_ids, $database, $row);
  }
}