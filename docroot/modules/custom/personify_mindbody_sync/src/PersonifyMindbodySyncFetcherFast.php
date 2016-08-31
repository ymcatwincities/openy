<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcherFast.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcherFast extends PersonifyMindbodySyncFetcherBase {

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    // For testing use: "PersonifyMindbodySyncWrapper::INITIAL_DATE".
    $date = $this->wrapper->timestampToPersonifyDate(REQUEST_TIME - PersonifyMindbodySyncWrapper::DATE_OFFSET);
    $date = PersonifyMindbodySyncWrapper::INITIAL_DATE;
    $orders = $this->getData($date);
    $this->wrapper->setSourceData($orders);
  }

}
