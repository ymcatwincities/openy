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
    $this->logger->info('Fast fetcher started.');

    $date = $this->wrapper->getCurrentTime();
    $orders = $this->getData($date);
    $this->wrapper->setSourceData($orders);

    $msg = 'Fast fetcher has fetched %num items.';
    $this->logger->info($msg, ['%num' => count($orders)]);
  }

}
