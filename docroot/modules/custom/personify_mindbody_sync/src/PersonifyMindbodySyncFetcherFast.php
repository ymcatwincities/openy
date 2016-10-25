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
    $this->logger->debug('The Pull from Personify has been started.');

    $date = $this->wrapper->getCurrentTime();
    $orders = $this->getData($date);

    foreach ($orders as $i => $order) {
      // Do not proceed with "body trainings".
      if (!$this->isPersonalTrainingProductCode($order)) {
        unset($orders[$i]);
      }
    }

    $this->wrapper->setSourceData($orders);

    $msg = 'The Pull from Personify has been finished. %num items have been fetched.';
    $this->logger->debug($msg, ['%num' => count($orders)]);
  }

}
