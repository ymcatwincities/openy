<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;

/**
 * Class YmcaQueryBuilder.
 */
class YmcaComponentTreeBuilder {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A list of components.
   *
   * @var array
   */
  protected $components = [];

  /**
   * A component tree.
   *
   * @var array
   */
  protected $tree = [];

  /**
   * YmcaComponentTreeBuilder constructor.
   *
   * @param int $page_id
   *   Site page ID.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct($page_id, Connection $database) {
    $this->database = $database;
    $this->setComponents($this->fetchComponents($page_id));
    $this->tree = $this->buildTree();
  }

  /**
   * Get tree.
   *
   * @return array
   *   Component tree.
   */
  public function getTree() {
    return $this->tree;
  }

  /**
   * Set flat list of components.
   *
   * @param array $components
   *   Array of components.
   */
  public function setComponents(array $components) {
    $this->components = $components;
  }

  /**
   * Get flat list of components.
   *
   * @return array
   *   Array with components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Get all components on the page from the database.
   *
   * @param int $page_id
   *   Page ID.
   *
   * @return array|false
   *   Array of components or FALSE
   */
  protected function fetchComponents($page_id) {
    $query = $this->database->select('amm_site_page_component', 'c');
    $query->fields('c')
      ->condition('site_page_id', $page_id);
    $query->orderBy('content_area_index', 'ASC');
    $query->orderBy('sequence_index', 'ASC');
    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Get all children.
   *
   * @param array $elements
   *   List of elements to search in.
   * @param int $parent_id
   *   Parent ID to start.
   *
   * @return array
   *   Array of children.
   */
  protected function getChildren(array $elements, $parent_id = 0) {
    $branch = array();

    foreach ($elements as $element) {
      if ($element['parent_component_id'] == $parent_id) {
        $children = $this->getChildren($elements, $element['site_page_component_id']);
        if ($children) {
          $element['children'] = $children;
        }
        $branch[$element['site_page_component_id']] = $element;
      }
    }

    return $branch;
  }

  /**
   * Build a tree from components.
   *
   * @return array
   *   Tree of components.
   */
  public function buildTree() {
    $tree = [];

    foreach ($this->components as $item) {
      if (is_null($item['parent_component_id'])) {
        $id = $item['site_page_component_id'];
        $tree[$id] = $item;
        $children = $this->getChildren($this->components, $id);
        if (count($children)) {
          $tree[$id]['children'] = $children;
        }
      }
    }

    return $tree;
  }

}
