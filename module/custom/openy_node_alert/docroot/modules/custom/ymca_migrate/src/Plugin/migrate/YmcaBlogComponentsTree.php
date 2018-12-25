<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Class YmcaBlogComponentsTree.
 *
 * @package Drupal\ymca_migrate
 */
class YmcaBlogComponentsTree extends AmmComponentsTree {

  /**
   * Database SqlBase object.
   *
   * @var \Drupal\migrate\Plugin\migrate\source\SqlBase
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
   * @var array
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
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Row $row
   *   Row that is processed within a Tree.
   */
  protected function __construct($skip_ids, SqlBase &$database, Row $row) {
    $this->database = &$database;
    $this->row = $row;
    $this->tree = [];
    parent::__construct('blog', $skip_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    // @todo Use setSkipIds data.

    // Get all component data.
    $select = $this->database->getDatabase()->select('abe_blog_post_component', 'c');
    $select->fields('c')
      ->condition(
        'blog_post_id',
        $this->row->getSourceProperty('blog_post_id')
      );
    $components = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // Get components tree, where each component has its children.

    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $this->tree[$item['blog_post_component_id']] = $item;
      }
      else {
        $this->tree[$item['parent_component_id']]['children'][$item['blog_post_component_id']] = $item;
      }
    }

    // @todo Sort components withing the same area by weight.
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  static public function init($skip_ids, SqlBase &$database, Row $row) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skip_ids, $database, $row);
  }

}
