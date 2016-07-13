<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcherSlow extends PersonifyMindbodySyncFetcherBase implements PersonifyMindbodySyncFetcherInterface{

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    if ($timestamp = $this->findFirstFailDate()) {
      $date = $this->convertTime($timestamp);
    }
    else {
      $date = PersonifyMindbodySyncWrapper::INITIAL_DATE;
    }

    $this->wrapper->setSourceData($date);
  }

  /**
   * Get the timestamp of the first failed item.
   *
   * @return mixed
   *   First fail timestamp or bool.
   */
  private function findFirstFailDate() {
    return FALSE;
  }
}
