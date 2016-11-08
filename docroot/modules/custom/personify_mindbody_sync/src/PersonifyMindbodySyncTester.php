<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncTester.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncTester {

  /**
   * The pusher.
   *
   * @var \Drupal\personify_mindbody_sync\PersonifyMindbodySyncPusherFast
   */
  protected $pusher;

  /**
   * Wrapper.
   *
   * @var \Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * PersonifyMindbodySyncTester constructor.
   *
   * @param \Drupal\personify_mindbody_sync\PersonifyMindbodySyncPusherFast $pusher
   *   Pusher.
   * @param \Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper $wrapper
   *   Wrapper.
   */
  public function __construct(PersonifyMindbodySyncPusherFast $pusher, PersonifyMindbodySyncWrapper $wrapper) {
    $this->pusher = $pusher;
    $this->wrapper = $wrapper;
  }

  /**
   * Test PersonifyMindbodySyncPusherBase::sendNotification().
   */
  public function testSendNotification() {
    $order = $this->wrapper->mockOrder();
    $this->pusher->sendNotification($order, 10);
  }

}
