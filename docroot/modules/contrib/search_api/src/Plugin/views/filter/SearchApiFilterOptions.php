<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilterOptions.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a filter for filtering on fields with a fixed set of possible values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_options")
 */
class SearchApiFilterOptions extends SearchApiFilter {

  /**
   * Stores the values which are available on the form.
   *
   * @var array|null
   */
  protected $valueOptions;

  /**
   * The type of form element used to display the options.
   *
   * @var string
   */
  protected $valueFormType = 'checkboxes';

  /**
   * Fills SearchApiFilterOptions::$valueOptions with all possible options.
   */
  protected function getValueOptions() {
    if (isset($this->valueOptions)) {
      return;
    }

    // @todo This obviously needs a different solution.
    $wrapper = $this->get_wrapper();
    if ($wrapper) {
      $this->valueOptions = $wrapper->optionsList('view');
    }
    else {
      $this->valueOptions = array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    $options = array(
      '=' => $this->t('Is one of'),
      'all of' => $this->t('Is all of'),
      '<>' => $this->t('Is none of'),
      'empty' => $this->t('Is empty'),
      'not empty' => $this->t('Is not empty'),
    );
    // "Is all of" doesn't make sense for single-valued fields.
    if (empty($this->definition['multi-valued'])) {
      unset($options['all of']);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['reduce'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['reduce'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $form['expose']['reduce'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Limit list to selected items'),
      '#description' => $this->t('If checked, the only items presented to the user will be the ones selected here.'),
      '#default_value' => !empty($this->options['expose']['reduce']),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $this->getValueOptions();
    if (!empty($this->options['expose']['reduce']) && $form_state->get('exposed')) {
      $options = $this->reduceValueOptions();
    }
    else {
      $options = $this->valueOptions;
    }

    $form['value'] = array(
      '#type' => $this->valueFormType,
      '#title' => !$form_state->get('exposed') ? $this->t('Value') : '',
      '#options' => $options,
      '#multiple' => TRUE,
      '#size' => min(4, count($options)),
      '#default_value' => is_array($this->value) ? $this->value : array(),
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
   * Retrieves the reduced options list to use for the exposed filter.
   *
   * @return string[]
   *   An options list for the values list, with only the ones selected in the
   *   admin UI included.
   */
  protected function reduceValueOptions() {
    foreach ($this->valueOptions as $id => $option) {
      if (!isset($this->options['value'][$id])) {
        unset($this->valueOptions[$id]);
      }
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  // @todo Is this still needed in D8?
  public function valueSubmit($form, FormStateInterface $form_state) {
    // Drupal's FAPI system automatically puts '0' in for any checkbox that
    // was not set, and the key to the checkbox if it is set.
    // Unfortunately, this means that if the key to that checkbox is 0,
    // we are unable to tell if that checkbox was set or not.

    // Luckily, the '#value' on the checkboxes form actually contains
    // *only* a list of checkboxes that were set, and we can use that
    // instead.

    $form_state->setValueForElement($form['value'], $form['value']['#value']);
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

    if (!is_array($this->value)) {
      return '';
    }

    $operator_options = $this->operatorOptions();
    $operator = $operator_options[$this->operator];
    $values = '';

    // Remove every element which is not known.
    // @todo Why? Doesn't FAPI already prevent this?
    $this->getValueOptions();
    foreach ($this->value as $i => $value) {
      if (!isset($this->valueOptions[$value])) {
        unset($this->value[$i]);
      }
    }
    // Choose different kind of ouput for 0, a single and multiple values.
    if (count($this->value) == 0) {
      return $this->operator != '<>' ? $this->t('none') : $this->t('any');
    }
    elseif (count($this->value) == 1) {
      switch ($this->operator) {
        case '=':
        case 'all of':
          $operator = '=';
          break;

        case '<>':
          $operator = '<>';
          break;
      }
      // If there is only a single value, use just the plain operator, = or <>.
      $operator = Html::escape($operator);
      $values = Html::escape($this->valueOptions[reset($this->value)]);
    }
    else {
      foreach ($this->value as $value) {
        if ($values !== '') {
          $values .= ', ';
        }
        if (Unicode::strlen($values) > 20) {
          $values .= '…';
          break;
        }
        $values .= Html::escape($this->valueOptions[$value]);
      }
    }

    return $operator . (($values !== '') ? ' ' . $values : '');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->operator === 'empty') {
      $this->query->addCondition($this->realField, NULL, '=', $this->options['group']);
      return;
    }
    if ($this->operator === 'not empty') {
      $this->query->addCondition($this->realField, NULL, '<>', $this->options['group']);
      return;
    }

    // Extract the value.
    while (is_array($this->value) && count($this->value) == 1) {
      $this->value = reset($this->value);
    }

    // Determine operator and conjunction. The defaults are already right for
    // "all of".
    $operator = '=';
    $conjunction = 'AND';
    switch ($this->operator) {
      case '=':
        $conjunction = 'OR';
        break;

      case '<>':
        $operator = '<>';
        break;
    }

    // If the value is an empty array, we either want no filter at all (for
    // "is none of"), or want to find only items with no value for the field.
    if ($this->value === array()) {
      if ($operator != '<>') {
        $this->query->addCondition($this->realField, NULL, '=', $this->options['group']);
      }
      return;
    }

    if (is_scalar($this->value) && $this->value !== '') {
      $this->query->addCondition($this->realField, $this->value, $operator, $this->options['group']);
    }
    elseif ($this->value) {
      $conditions = $this->query->createConditionGroup($conjunction);
      // $conditions will be NULL if there were errors in the query.
      if ($conditions) {
        foreach ($this->value as $v) {
          $conditions->addCondition($this->realField, $v, $operator);
        }
        $this->query->addConditionGroup($conditions, $this->options['group']);
      }
    }
  }

}
