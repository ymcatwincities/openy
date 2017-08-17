<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Interface FetcherInterface.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
interface FetcherInterface {

  /**
   * Fetch data.
   */
  public function fetch();

}
