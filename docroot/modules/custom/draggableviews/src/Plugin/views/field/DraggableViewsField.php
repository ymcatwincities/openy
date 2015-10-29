<?php

/**
 * @file
 * Contains \Drupal\draggableviews\Plugin\views\field\DraggableViewsField.
 */

namespace Drupal\draggableviews\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\field\Field;
use Drupal\Core\Render\Markup;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("draggable_views_field")
 */
class DraggableViewsField extends Field {

  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $i = 0;
  }


  public function render_item($count, $item) {
    // Using internal method. @todo Reckeck after drupal stable release.
    return Markup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }
}
