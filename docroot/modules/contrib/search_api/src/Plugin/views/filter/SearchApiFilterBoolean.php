<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilterBoolean.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a filter for filtering on boolean values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_boolean")
 */
class SearchApiFilterBoolean extends SearchApiFilter {

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    while (is_array($this->value)) {
      $this->value = $this->value ? array_shift($this->value) : NULL;
    }
    $form['value'] = array(
      '#type' => 'select',
      '#title' => !$form_state->get('exposed') ? $this->t('Value') : '',
      '#options' => array(1 => $this->t('True'), 0 => $this->t('False')),
      '#default_value' => isset($this->value) ? $this->value : '',
    );
  }

}
