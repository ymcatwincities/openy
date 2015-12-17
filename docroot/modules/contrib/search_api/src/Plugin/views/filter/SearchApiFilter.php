<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilter.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Defines a filter for adding general Search API conditions to the query.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_filter")
 */
class SearchApiFilter extends FilterPluginBase {

  /**
   * The associated Views query object.
   *
   * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery
   */
  public $query;

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    return array(
      '<' => $this->t('Is less than'),
      '<=' => $this->t('Is less than or equal to'),
      '=' => $this->t('Is equal to'),
      '<>' => $this->t('Is not equal to'),
      '>=' => $this->t('Is greater than or equal to'),
      '>' => $this->t('Is greater than'),
      'empty' => $this->t('Is empty'),
      'not empty' => $this->t('Is not empty'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // @todo Hopefully we can now be more sure of what we get in $this->value.
    while (is_array($this->value) && count($this->value) < 2) {
      $this->value = $this->value ? reset($this->value) : NULL;
    }
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => !$form_state->get('exposed') ? $this->t('Value') : '',
      '#size' => 30,
      '#default_value' => isset($this->value) ? $this->value : '',
    );

    // Hide the value box if the operator is 'empty' or 'not empty'.
    // Radios share the same selector so we have to add some dummy selector.
    if (!$form_state->get('exposed')) {
      $form['value']['#states']['visible'] = array(
        ':input[name="options[operator]"],dummy-empty' => array('!value' => 'empty'),
        ':input[name="options[operator]"],dummy-not-empty' => array('!value' => 'not empty'),
      );
    }
    elseif (!empty($this->options['expose']['use_operator'])) {
      $name = $this->options['expose']['operator_id'];
      $form['value']['#states']['visible'] = array(
        ':input[name="' . $name . '"],dummy-empty' => array('!value' => 'empty'),
        ':input[name="' . $name . '"],dummy-not-empty' => array('!value' => 'not empty'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    if ($this->operator === 'empty') {
      return $this->t('is empty');
    }
    if ($this->operator === 'not empty') {
      return $this->t('is not empty');
    }

    return Html::escape((string) $this->operator) . ' ' . Html::escape((string) $this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->operator === 'empty') {
      $this->query->addCondition($this->realField, NULL, '=', $this->options['group']);
    }
    elseif ($this->operator === 'not empty') {
      $this->query->addCondition($this->realField, NULL, '<>', $this->options['group']);
    }
    else {
      while (is_array($this->value)) {
        $this->value = $this->value ? reset($this->value) : NULL;
      }
      if (strlen($this->value) > 0) {
        $this->query->addCondition($this->realField, $this->value, $this->operator, $this->options['group']);
      }
    }
  }

}
