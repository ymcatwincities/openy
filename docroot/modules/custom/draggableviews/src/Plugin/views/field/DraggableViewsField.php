<?php

/**
 * @file
 * Contains \Drupal\draggableviews\Plugin\views\field\DraggableViewsField.
 */

namespace Drupal\draggableviews\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\draggableviews\DraggableViews;
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
    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $draggableviews = new DraggableViews($this->view);

    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = array(
        '#tree' => TRUE,
      );

      // Item to keep id of the entity.
      $form[$this->options['id']][$row_index]['id'] = array(
        '#type' => 'hidden',
        '#value' => $row->{$this->definition['entity field']},
        '#attributes' => array('class' => 'draggableviews-id'),
      );

      // Add parent.
      $form[$this->options['id']][$row_index]['parent'] = array(
        '#type' => 'hidden',
        '#default_value' => $draggableviews->getParent($row_index),
        '#attributes' => array('class' => 'draggableviews-parent'),
      );
    }

    if (\Drupal::currentUser()->hasPermission('access draggableviews')) {
      $options = [
        'table_id' => $draggableviews->getHtmlId(),
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'draggableviews-parent',
        'subgroup' => 'draggableviews-parent',
        'source' => 'draggableviews-id'
      ];
      drupal_attach_tabledrag($form, $options);
    }
  }

}
