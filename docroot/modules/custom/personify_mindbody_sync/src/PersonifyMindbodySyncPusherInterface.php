<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Interface PersonifyMindbodySyncPusherInterface.
 *
 * @package Drupal\personify_mindbody_sync
 */
interface PersonifyMindbodySyncPusherInterface {

  /**
   * Push entities to Mindbody.
   */
  public function push();

}
