<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilterText.
 */

namespace Drupal\search_api\Plugin\views\filter;

/**
 * Defines a filter for filtering on fulltext fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_text")
 */
class SearchApiFilterText extends SearchApiFilter {

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    return array(
      '=' => $this->t('contains'),
      '<>' => $this->t("doesn't contain"),
    );
  }

}
