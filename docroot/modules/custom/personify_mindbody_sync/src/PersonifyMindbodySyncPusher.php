<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncPusher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusher implements PersonifyMindbodySyncPusherInterface {

  /**
   * Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * PersonifyMindbodySyncPusher constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Data wrapper.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper) {
    $this->wrapper = $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    $a = 10;
  }

}
