<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Interface PersonifyMindbodySyncFetcherInterface.
 *
 * @package Drupal\personify_mindbody_sync
 */
interface PersonifyMindbodySyncFetcherInterface {

  /**
   * Fetch data from Personify and push them to data wrapper.
   */
  public function fetch();

}
