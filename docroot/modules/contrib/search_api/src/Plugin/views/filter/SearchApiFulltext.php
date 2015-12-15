<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFulltext.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\search_api\Entity\Index;

/**
 * Defines a filter for adding a fulltext search to the view.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_fulltext")
 */
class SearchApiFulltext extends SearchApiFilterText {

  /**
   * {@inheritdoc}
   */
  public function showOperatorForm(&$form, FormStateInterface $form_state) {
    $this->operatorForm($form, $form_state);
    $form['operator']['#description'] = $this->t('This operator only applies when using "Search keys" as the "Use as" setting.');
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    return array(
      'AND' => $this->t('Contains all of these words'),
      'OR' => $this->t('Contains any of these words'),
      'NOT' => $this->t('Contains none of these words'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'AND';

    $options['min_length']['default'] = '';
    $options['fields']['default'] = array();

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields = $this->getFulltextFields();
    if (!empty($fields)) {
      $form['fields'] = array(
        '#type' => 'select',
        '#title' => $this->t('Searched fields'),
        '#description' => $this->t('Select the fields that will be searched. If no fields are selected, all available fulltext fields will be searched.'),
        '#options' => $fields,
        '#size' => min(4, count($fields)),
        '#multiple' => TRUE,
        '#default_value' => $this->options['fields'],
      );
    }
    else {
      $form['fields'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }
    if (isset($form['expose'])) {
      $form['expose']['#weight'] = -5;
    }

    $form['min_length'] = array(
      '#title' => $this->t('Minimum keyword length'),
      '#description' => $this->t('Minimum length of each word in the search keys. Leave empty to allow all words.'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->options['min_length'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    // Only validate exposed input.
    if (empty($this->options['exposed']) || empty($this->options['expose']['identifier'])) {
      return;
    }

    // We only need to validate if there is a minimum word length set.
    if ($this->options['min_length'] < 2) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    $input = &$form_state->getValues()[$identifier];

    if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
      $this->operator = $this->options['group_info']['group_items'][$input]['operator'];
      $input = &$this->options['group_info']['group_items'][$input]['value'];
    }

    // If there is no input, we're fine.
    if (!trim($input)) {
      return;
    }

    $words = preg_split('/\s+/', $input);
    foreach ($words as $i => $word) {
      if (Unicode::strlen($word) < $this->options['min_length']) {
        unset($words[$i]);
      }
    }
    if (!$words) {
      $vars['@count'] = $this->options['min_length'];
      $msg = $this->t('You must include at least one positive keyword with @count characters or more.', $vars);
      $form_state->setError($form[$identifier], $msg);
    }
    $input = implode(' ', $words);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    while (is_array($this->value)) {
      $this->value = $this->value ? reset($this->value) : '';
    }
    // Catch empty strings entered by the user, but not "0".
    // @todo Is this needed? It seems Views doesn't call filters with empty
    //   values by default anyways.
    if ($this->value === '') {
      return;
    }
    $fields = $this->options['fields'];
    $fields = $fields ? $fields : array_keys($this->getFulltextFields());

    // If something already specifically set different fields, we silently fall
    // back to mere filtering.
    $old = $this->query->getFulltextFields();
    $conditions = $old && (array_diff($old, $fields) || array_diff($fields, $old));

    if ($conditions) {
      $conditions = $this->query->createConditionGroup('OR');
      $op = $this->operator === 'NOT' ? '<>' : '=';
      foreach ($fields as $field) {
        $conditions->addCondition($field, $this->value, $op);
      }
      $this->query->addConditionGroup($conditions);
      return;
    }

    // If the operator was set to OR or NOT, set OR as the conjunction. (It is
    // also set for NOT since otherwise it would be "not all of these words".)
    if ($this->operator != 'AND') {
      $this->query->setOption('conjunction', 'OR');
    }

    $this->query->setFulltextFields($fields);
    $old = $this->query->getKeys();
    $old_original = $this->query->getOriginalKeys();
    $this->query->keys($this->value);
    if ($this->operator == 'NOT') {
      $keys = &$this->query->getKeys();
      if (is_array($keys)) {
        $keys['#negation'] = TRUE;
      }
      else {
        // We can't know how negation is expressed in the server's syntax.
      }
    }

    // If there were fulltext keys set, we take care to combine them in a
    // meaningful way (especially with negated keys).
    if ($old) {
      $keys = &$this->query->getKeys();
      // Array-valued keys are combined.
      if (is_array($keys)) {
        // If the old keys weren't parsed into an array, we instead have to
        // combine the original keys.
        if (is_scalar($old)) {
          $keys = "($old) ({$this->value})";
        }
        else {
          // If the conjunction or negation settings aren't the same, we have to
          // nest both old and new keys array.
          if (!empty($keys['#negation']) != !empty($old['#negation']) || $keys['#conjunction'] != $old['#conjunction']) {
            $keys = array(
              '#conjunction' => 'AND',
              $old,
              $keys,
            );
          }
          // Otherwise, just add all individual words from the old keys to the
          // new ones.
          else {
            foreach (Element::children($old) as $i) {
              $keys[] = $old[$i];
            }
          }
        }
      }
      // If the parse mode was "direct" for both old and new keys, we
      // concatenate them and set them both via method and reference (to also
      // update the originalKeys property.
      elseif (is_scalar($old_original)) {
        $combined_keys = "($old_original) ($keys)";
        $this->query->keys($combined_keys);
        $keys = $combined_keys;
      }
    }
  }

  /**
   * Retrieves a list of all available fulltext fields.
   *
   * @return string[]
   *   An options list of fulltext field identifiers mapped to their prefixed
   *   labels.
   */
  protected function getFulltextFields() {
    $fields = array();
    $index = Index::load(substr($this->table, 17));

    $fields_info = $index->getFields();
    foreach ($index->getFulltextFields() as $field_id) {
      $fields[$field_id] = $fields_info[$field_id]->getPrefixedLabel();
    }

    return $fields;
  }

}
