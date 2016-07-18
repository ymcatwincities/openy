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
    $orders = $this->getData($this->convertTime(REQUEST_TIME - PersonifyMindbodySyncWrapper::DATE_OFFSET));
    $this->wrapper->setSourceData($orders);
  }

}
