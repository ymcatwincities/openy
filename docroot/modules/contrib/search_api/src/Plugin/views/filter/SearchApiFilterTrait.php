<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilterTrait.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;

/**
 * Provides a trait to use for Search API Views filters.
 */
trait SearchApiFilterTrait {

  /**
   * Overrides the Views handlers' ensureMyTable() method.
   *
   * This is done since adding a table to a Search API query is neither
   * necessary nor possible, but we still want to stay as compatible as possible
   * to the default SQL query plugin.
   */
  public function ensureMyTable() {}

  /**
   * Adds a form for entering the value or values for the filter.
   *
   * Overridden to remove fields that won't be used (but aren't hidden either
   * because of a small bug/glitch in the original form code – see #2637674).
   *
   * @see \Drupal\views\Plugin\views\filter\FilterPluginBase::valueForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (isset($form['value']['min'])) {
      if (!$this->operatorValues(2)) {
        unset($form['value']['min'], $form['value']['max']);
      }
    }
  }

  /**
   * Returns the active search index.
   *
   * @return \Drupal\search_api\IndexInterface|null
   *   The search index to use with this filter, or NULL if none could be
   *   loaded.
   */
  protected function getIndex() {
    if ($this->getQuery()) {
      return $this->getQuery()->getIndex();
    }
    $base_table = $this->view->storage->get('base_table');
    return SearchApiQuery::getIndexFromTable($base_table);
  }

  /**
   * Adds a filter to the search query.
   *
   * Overridden to avoid errors because of SQL-specific functionality being used
   * when "Many To One" is used as a base class.
   *
   * @see \Drupal\views\Plugin\views\filter\ManyToOne::opHelper()
   */
  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }
    // @todo Use "IN"/"NOT IN" instead, once available.
    $conjunction = $this->operator == 'or' ? 'OR' : 'AND';
    $operator = $this->operator == 'not' ? '<>' : '=';
    $filter = $this->getQuery()->createConditionGroup($conjunction);
    foreach ($this->value as $value) {
      $filter->addCondition($this->realField, $value, $operator);
    }
    $this->getQuery()->addConditionGroup($filter, $this->options['group']);
  }

  /**
   * Retrieves the query plugin.
   *
   * @return \Drupal\search_api\Plugin\views\query\SearchApiQuery
   *   The query plugin.
   */
  public function getQuery() {
    return $this->query;
  }

}
