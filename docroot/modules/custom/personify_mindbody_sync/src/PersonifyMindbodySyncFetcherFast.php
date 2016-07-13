<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcherFast extends PersonifyMindbodySyncFetcherBase implements PersonifyMindbodySyncFetcherInterface{

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $orders = $this->getData($this->convertTime(REQUEST_TIME));
    $this->wrapper->setSourceData($orders);
  }

}
