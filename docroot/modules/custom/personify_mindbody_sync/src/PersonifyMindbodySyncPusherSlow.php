<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncPusherSlow.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusherSlow extends PersonifyMindbodySyncPusherBase {

  /**
   * {@inheritdoc}
   */
  public function push() {
    if (!$push = $this->getNotPushedOrders()) {
      $this->logger->debug('All orders have been already pushed. Exit.');
      return;
    }

    $this->pushClientsSingle();
    $this->pushOrders($push);
  }

}
