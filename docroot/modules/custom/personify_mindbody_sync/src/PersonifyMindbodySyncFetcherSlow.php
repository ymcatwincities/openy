<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcherSlow.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcherSlow extends PersonifyMindbodySyncFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    // No failed orders - start from initial date.
    $date = PersonifyMindbodySyncWrapper::INITIAL_DATE;

    // Try to find the date of the first push failure.
    if ($timestamp = $this->wrapper->findFirstFailTime()) {
      $date = $this->wrapper->timestampToPersonifyDate($timestamp);
    }

    $data = $this->getData($date);
    $this->wrapper->setSourceData($data);
  }

}
