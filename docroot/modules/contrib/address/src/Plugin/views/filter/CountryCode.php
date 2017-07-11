<?php

namespace Drupal\address\Plugin\views\filter;

/**
 * Filter by country.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("country_code")
 */
class CountryCode extends CountryAwareInOperatorBase {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = $this->getAvailableCountries();
    }

    return $this->valueOptions;
  }

}
