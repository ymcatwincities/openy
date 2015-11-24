<?php
/**
 * @file
 * Class for getting children tree by top menu item.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Class YmcaPagesQuery
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaPagesQuery extends AmmPagesQuery {

  /**
   * Database to be used as active.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Tree of pages.
   *
   * $var array
   */
  protected $tree;

  /**
   * Singleton instance.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesQuery
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
   * @return \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesQuery $this
   *   Returns itself.
   */
  protected function __construct($skip_ids, $needed_ids, SqlBase $database) {
    $this->database = $database;
    $this->tree = [];
    parent::__construct('page', $skip_ids, $needed_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsByParent($id) {
    $query = $this->select('amm_site_page', 'p');
    $query->fields('p',
      [
        'site_page_id',
        'page_title',
        'theme_id',
      ]);
    $query->condition('site_page_id', $ymca_page_query->getNeededIds(), 'IN');
    $skipped_ids = $ymca_page_query->getSkippedIds();
    if (!empty($skipped_ids)) {
      $query->condition(
        'site_page_id',
        $skipped_ids,
        'NOT IN'
      );
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  static public function init(
    $skip_ids,
    $needed_ids,
    SqlBase $migrate_database
  ) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skip_ids, $needed_ids, $migrate_database);
  }
}