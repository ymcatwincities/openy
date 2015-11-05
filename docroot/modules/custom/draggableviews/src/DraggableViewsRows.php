<?php

/**
 * @file
 * Contains \Drupal\draggableviews\DraggabaleViewsRows class.
 */

namespace Drupal\draggableviews;

/**
 * Class DraggableViewsRows.
 */
class DraggableViewsRows {

  /**
   * Views result rows.
   *
   * @var array
   */
  protected $rows;

  /**
   * Constructs DraggableViewsRows object.
   *
   * @param array $rows
   *   Views result array of rows.
   */
  public function __construct(array $rows) {
    $this->rows = $rows;
  }

  /**
   * Get index by name and id.
   */
  public function getIndex($name, $id) {
    foreach ($this->rows as $item) {
      if ($item->$name == $id) {
        return $item->index;
      }
    }
    return FALSE;
  }

  /**
   * Get depth.
   */
  public function getDepth($index) {
    if (!isset($this->rows[$index])) {
      return FALSE;
    }
    $row = $this->rows[$index];
    // If parent is available, set parent's depth +1.
    return (!empty($row->draggableviews_structure_parent)) ? $this->getDepth($this->getIndex('nid', $row->draggableviews_structure_parent)) + 1 : 0;
  }

  /**
   * Get parent.
   */
  public function getParent($index) {
    return isset($this->rows[$index]->draggableviews_structure_parent) ? $this->rows[$index]->draggableviews_structure_parent : 0;
  }

}
