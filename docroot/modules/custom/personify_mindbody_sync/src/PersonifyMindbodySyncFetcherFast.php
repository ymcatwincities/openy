<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcherFast extends PersonifyMindbodySyncFetcherBase implements PersonifyMindbodySyncFetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $orders = $this->getData($this->convertTime(REQUEST_TIME - PersonifyMindbodySyncWrapper::DATE_OFFSET));
    $this->wrapper->setSourceData($orders);
  }

}
