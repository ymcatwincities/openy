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
    // Try to find the date of the first push failure.
    if ($timestamp = $this->wrapper->findFirstFailTime()) {
      $date = $this->wrapper->timestampToPersonifyDate($timestamp);
    }
    else {
      // No failed orders - start from initial date.
      $date = PersonifyMindbodySyncWrapper::INITIAL_DATE;
    }

    $data = $this->getData($date);
    $this->wrapper->setSourceData($data);
  }

}
