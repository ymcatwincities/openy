<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\field\SearchApiExcerpt.
 */

namespace Drupal\search_api\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a field displaying a search result's excerpt, if available.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_excerpt")
 */
class SearchApiExcerpt extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value, 'xss');
  }

}
