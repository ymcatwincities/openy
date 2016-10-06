<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Interface PersonifyMindbodySyncProxyInterface.
 *
 * @package Drupal\personify_mindbody_sync
 */
interface PersonifyMindbodySyncProxyInterface {

  /**
   * Save entities to Drupal database and push them to data wrapper.
   */
  public function saveEntities();

}
