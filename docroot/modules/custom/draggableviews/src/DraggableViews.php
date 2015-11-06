<?php

/**
 * @file
 * Contains \Drupal\draggableviews\DraggableViews class.
 */

namespace Drupal\draggableviews;

use Drupal\views\ViewExecutable;
use Drupal\Component\Utility\Html;

/**
 * Class DraggableViews.
 */
class DraggableViews {

  /**
   * The view.
   *
   * @var \Drupal\views\ViewExecutable $view
   */
  protected $view;

  /**
   * Constructs DraggableViewsRows object.
   *
   * @param \Drupal\views\ViewExecutable $viewExecutable
   *   Views object.
   */
  public function __construct(ViewExecutable $viewExecutable) {
    $this->view = $viewExecutable;
  }

  /**
   * Get index by name and id.
   */
  public function getIndex($name, $id) {
    foreach ($this->view->result as $item) {
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
    if (!isset($this->view->result[$index])) {
      return FALSE;
    }
    $row = $this->view->result[$index];
    // If parent is available, set parent's depth +1.
    return (!empty($row->draggableviews_structure_parent)) ? $this->getDepth($this->getIndex('nid', $row->draggableviews_structure_parent)) + 1 : 0;
  }

  /**
   * Get parent.
   */
  public function getParent($index) {
    return isset($this->view->result[$index]->draggableviews_structure_parent) ? $this->view->result[$index]->draggableviews_structure_parent : 0;
  }

  /**
   * get HTML id for draggableviews table.
   */
  public function getHtmlId() {
    return Html::getId('draggableviews-table-' . $this->view->id() . '-' . $this->view->current_display);
  }

}
