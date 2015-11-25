<?php
/**
 * @file
 * Class that would be used for getting tree of Pages to be migrated into Drupal CT.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Class YmcaBlogComponentsTree.
 *
 * @package Drupal\ymca_migrate
 */
class YmcaPageComponentsTree extends AmmComponentsTree {

  /**
   * Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Row that is processed within a Tree.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Tree of components.
   *
   * $var array.
   */
  protected $tree;

  /**
   * Singleton instance.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\AmmComponentsTree
   */
  static private $instance;

  /**
   * YmcaBlogComponentsTree constructor.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param \Drupal\Core\Database\Connection $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Row $row
   *   Row that is processed within a Tree.
   */
  protected function __construct($skip_ids, Connection $database, Row $row) {
    $this->database = $database;
    $this->row = $row;
    $this->tree = [];
    parent::__construct('page', $skip_ids);
  }

  /**
   * Method that can be used as approach for cyclic components.
   */
  public function getTree() {

    // @todo implement $parent_component_id argument approach.
    // @see YmcaPagesQuery::getAllChildren()
    // @todo Use setSkipIds data.
    // Some pages have NULL title, so create one.
    if (!$this->row->getSourceProperty('page_title')) {
      $this->row->setSourceProperty('page_title', t('Title'));
    }
    // Get all component data.
    // Get all component data.
    $select = $this->database->select('amm_site_page_component', 'c');
    $select->fields('c')
      ->condition(
        'site_page_id',
        $this->row->getSourceProperty('site_page_id')
      );
    $select->orderBy('content_area_index', 'ASC');
    $select->orderBy('sequence_index', 'ASC');
    $components = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // Write parents.
    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $this->tree[$item['site_page_component_id']] = $item;
      }
    }
    // Write children.
    foreach ($components as $item) {
      if (!is_null($item['parent_component_id'])) {
        $this->tree[$item['parent_component_id']]['children'][$item['site_page_component_id']] = $item;
      }
    }

    // @todo Sort components withing the same area by weight.
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  static public function init($skip_ids, Connection $database, Row $row) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skip_ids, $database, $row);
  }

}
