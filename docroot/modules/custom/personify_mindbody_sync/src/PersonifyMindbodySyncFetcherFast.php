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

    foreach ($orders as $order) {
      $msg = 'The order ID %id with line number %num and code %code has been fetched.';
      $this->logger->info(
        $msg,
        [
          '%id' => $order->OrderNo,
          '%num' => $order->OrderLineNo,
          '%code' => $order->ProductCode,
        ]
      );
    }

    $msg = 'Fast fetcher has fetched %num items.';
    $this->logger->info($msg, ['%num' => count($orders)]);
  }

}
