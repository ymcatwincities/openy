<?php

/**
 * @file
 * Contains \Drupal\draggableviews\Plugin\views\field\DraggableViewsField.
 */

namespace Drupal\draggableviews\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\draggableviews\DraggableViewsRows;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\Core\Render\Markup;

/**
 * Defines a draggableviews form element.
 *
 * @ViewsField("draggable_views_field")
 */
class DraggableViewsField extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function render_item($count, $item) {
    // @codingStandardsIgnoreEnd
    // Using internal method. @todo Reckeck after drupal stable release.
    return Markup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // todo: d7 has $this->field_alias. How to make in d8?
    $field_alias = 'nid';

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $range = count($this->view->result);
    $row_helper = new DraggableViewsRows($this->view->result);

    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = array(
        '#tree' => TRUE,
      );

      // Weight field select.
      $form[$this->options['id']][$row_index]['weight'] = array(
        '#type' => 'select',
        '#options' => range(-$range, $range),
        '#attributes' => array('class' => array('draggableviews-weight')),
        '#default_value' => $row_index + $range,
      );

      // Item to keep id of the entity.
      $form[$this->options['id']][$row_index]['id'] = array(
        '#type' => 'hidden',
        '#value' => $row->$field_alias,
        '#attributes' => array('class' => 'draggableviews-id'),
      );

      // Add parent.
      $form[$this->options['id']][$row_index]['parent'] = array(
        '#type' => 'hidden',
        '#default_value' => $row_helper->getParent($row_index),
        '#attributes' => array('class' => 'draggableviews-parent'),
      );

      // Add depth.
      $form[$this->options['id']][$row_index]['depth'] = array(
        '#type' => 'hidden',
        '#default_value' => $row_helper->getDepth($row_index),
        '#attributes' => array('class' => 'draggableviews-depth'),
      );
    }
  }

}
