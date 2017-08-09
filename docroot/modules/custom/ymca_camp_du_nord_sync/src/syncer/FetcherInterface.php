<?php

namespace Drupal\ymca_camp_du_nord_sync\syncer;

/**
 * Interface FetcherInterface.
 *
 * @package Drupal\ymca_camp_du_nord\syncer
 */
interface FetcherInterface {

  /**
   * Fetch data.
   */
  public function fetch();

}
