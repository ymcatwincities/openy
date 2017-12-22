<?php

namespace Drupal\file_entity\Plugin\views\filter;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * @ViewsFilter("file_scheme_type")
 */
class SchemeType extends InOperator {

  /**
   * {@inheritdoc}
   */
  function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = t('File Schema types');
      $types = \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::VISIBLE);
      $options = array();
      foreach ($types as $type => $info) {
        $options[$type] = $info['name'];
      }
      asort($options);
      $this->valueOptions = $options;
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $statements = array();

    $not_in = $this->operator == 'not in' ? TRUE : FALSE;
    $schema_operator = $not_in ? 'NOT LIKE' : 'LIKE';
    $composite = $not_in ? ' AND ' : ' OR ';

    foreach ($this->value as $schema) {
      $statements[] = 'uri ' . $schema_operator . ' \'' . db_like($schema) . '://%\'';
    }

    $this->query->addWhereExpression($this->options['group'], implode($composite, $statements));
  }
}
